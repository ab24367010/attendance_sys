#include <Wire.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <WiFi.h>
#include <WebServer.h>
#include <Preferences.h>
#include <HTTPClient.h>

// ---------------- OLED Setup ----------------
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// ---------------- RC522 Setup ----------------
#define SS_PIN 5
#define RST_PIN 4
MFRC522 mfrc522(SS_PIN, RST_PIN);

// ---------------- WiFi and Server ----------------
Preferences preferences;
WebServer server(80);
String ssid, password, serverURL;
bool wifiConnected = false;
unsigned long wifiLostTime = 0;
bool countdownStarted = false;

// ---------------- Helper Variables ----------------
unsigned long lastReadTime = 0;
String lastCardID = "";

// ---------------- BOOT Button ----------------
#define BOOT_PIN 0  // ESP32 Boot товч нь ихэнх самбар дээр GPIO0-д холбогдсон

void showMessage(String line1, String line2 = "") {
  display.clearDisplay();
  display.setCursor(0, 0);
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.println(line1);
  if (line2 != "") display.println(line2);
  display.display();
}

void startAccessPoint() {
  WiFi.softAP("NFC_Config", "12345678");
  IPAddress IP = WiFi.softAPIP();
  Serial.print("AP IP address: ");
  Serial.println(IP);

  showMessage("AP Mode Enabled", "SSID: NFC_Config");

  server.on("/", []() {
    String html = "<form action='/save' method='POST'>"
                  "SSID: <input name='ssid'><br>"
                  "Password: <input name='password'><br>"
                  "Server URL: <input name='server'><br>"
                  "<input type='submit' value='Save'>"
                  "</form>";
    server.send(200, "text/html", html);
  });

  server.on("/save", []() {
    ssid = server.arg("ssid");
    password = server.arg("password");
    serverURL = server.arg("server");

    preferences.begin("wifi", false);
    preferences.putString("ssid", ssid);
    preferences.putString("pass", password);
    preferences.putString("url", serverURL);
    preferences.end();

    server.send(200, "text/html", "Saved! Rebooting...");
    delay(2000);
    ESP.restart();
  });

  server.begin();
}

bool connectToWiFi() {
  preferences.begin("wifi", true);
  ssid = preferences.getString("ssid", "");
  password = preferences.getString("pass", "");
  serverURL = preferences.getString("url", "");
  preferences.end();

  if (ssid == "") return false;

  WiFi.begin(ssid.c_str(), password.c_str());

  showMessage("Connecting to", "WiFi...");

  unsigned long startAttemptTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startAttemptTime < 15000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    showMessage("WiFi connected!");
    delay(1000);
    return true;
  } else {
    showMessage("WiFi failed", "Switching to AP mode");
    delay(2000);
    return false;
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(BOOT_PIN, INPUT_PULLUP); // Boot товчийг шалгах

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println(F("OLED not found"));
    while (true);
  }

  showMessage("OLED initialized");
  delay(1000);

  SPI.begin(18, 19, 23, 5);
  mfrc522.PCD_Init();
  Serial.println("RC522 initialized");

  wifiConnected = connectToWiFi();
  if (!wifiConnected) {
    startAccessPoint();
  }

  showMessage("Please", "tap your card");
}

void loop() {
  // Boot товч дарагдсан үед WiFi-г салгаж AP горимд орно
  if (digitalRead(BOOT_PIN) == LOW) {
    showMessage("Boot pressed", "Switching to AP");

    WiFi.disconnect(true);
    delay(1000);

    wifiConnected = false;
    countdownStarted = false;
    startAccessPoint();
    return;
  }

  if (!wifiConnected) {
    server.handleClient();

    if (!countdownStarted) {
      wifiLostTime = millis();
      countdownStarted = true;
    } else if (millis() - wifiLostTime > 5 * 60 * 1000) {
      ESP.restart(); // Restart after 5 minutes to enter AP mode
    }
    return;
  }

  if (WiFi.status() != WL_CONNECTED) {
    showMessage("WiFi lost", "Retrying...");
    wifiConnected = false;
    countdownStarted = false;
    return;
  }

  if (millis() - lastReadTime < 2000) return;

  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String currentCardID = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      currentCardID += String(mfrc522.uid.uidByte[i], HEX);
    }

    Serial.println("Card ID: " + currentCardID);

    showMessage("Sending data...");

    HTTPClient http;
    http.begin(serverURL);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    String postData = "cardID=" + currentCardID;

    int httpResponseCode = http.POST(postData);
    http.end();

    if (httpResponseCode > 0) {
      showMessage("Data sent", "successfully");
    } else {
      showMessage("Failed to", "send data");
    }

    delay(3500);
    showMessage("Please", "tap your card");
    lastReadTime = millis();
  }
}
