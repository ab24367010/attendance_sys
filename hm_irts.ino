#include <Wire.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <WiFi.h>
#include <WebServer.h>
#include <Preferences.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <esp_wifi.h>
#include <esp_task_wdt.h>

// ---------------- OLED Setup ----------------
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
#define SCREEN_ADDRESS 0x3C
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// ---------------- RC522 Setup ----------------
#define SS_PIN 5
#define RST_PIN 4
MFRC522 mfrc522(SS_PIN, RST_PIN);

// ---------------- Hardware Configuration ----------------
// LEDs and Buzzer removed as per requirements

// ---------------- WiFi and Server ----------------
Preferences preferences;
WebServer server(80);
String ssid, password, serverURL;
bool wifiConnected = false;
unsigned long lastWiFiCheck = 0;
unsigned long wifiRetryInterval = 30000; // 30 seconds
int wifiRetryCount = 0;
const int maxWifiRetries = 5;

// ---------------- Card Reading Variables ----------------
unsigned long lastReadTime = 0;
String lastCardID = "";
const unsigned long cardReadDelay = 2000; // 2 seconds between reads
const unsigned long duplicateCardDelay = 10000; // 10 seconds for same card

// ---------------- Display and Status ----------------
unsigned long lastDisplayUpdate = 0;
const unsigned long displayUpdateInterval = 1000; // Update display every second
bool isInConfigMode = false;
unsigned long configModeStartTime = 0;
const unsigned long configModeTimeout = 300000; // 5 minutes

// ---------------- Boot Button ----------------
#define BOOT_PIN 0
unsigned long bootPressTime = 0;
bool bootPressed = false;
const unsigned long bootPressDelay = 3000; // 3 seconds hold

// ---------------- Status Tracking ----------------
enum SystemStatus {
  STATUS_STARTING,
  STATUS_WIFI_CONNECTING,
  STATUS_WIFI_CONNECTED,
  STATUS_WIFI_FAILED,
  STATUS_CONFIG_MODE,
  STATUS_READY,
  STATUS_CARD_PROCESSING,
  STATUS_ERROR
};

SystemStatus currentStatus = STATUS_STARTING;

// ---------------- Function Declarations ----------------
void initializeSystem();
void handleWiFiConnection();
void handleCardReading();
void updateDisplay();
void handleWebServer();
void showStatus(SystemStatus status, String message = "");

void setup() {
  Serial.begin(115200);
  
  // Initialize watchdog timer
  esp_task_wdt_init(30, true); // 30 second timeout
  esp_task_wdt_add(NULL);
  
  initializeSystem();
}

void loop() {
  // Reset watchdog
  esp_task_wdt_reset();
  
  // Handle boot button
  handleBootButton();
  
  if (isInConfigMode) {
    handleConfigMode();
  } else {
    handleNormalOperation();
  }
  
  // Update display periodically
  if (millis() - lastDisplayUpdate > displayUpdateInterval) {
    updateDisplay();
    lastDisplayUpdate = millis();
  }
  
  delay(50); // Small delay to prevent excessive CPU usage
}

void initializeSystem() {
  // Initialize pins
  pinMode(BOOT_PIN, INPUT_PULLUP);
  
  // Initialize OLED
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
    currentStatus = STATUS_ERROR;
    return;
  }
  
  showStatus(STATUS_STARTING, "Initializing...");
  delay(1000);
  
  // Initialize SPI and RC522
  SPI.begin(18, 19, 23, 5);
  mfrc522.PCD_Init();
  
  // Test RC522
  byte version = mfrc522.PCD_ReadRegister(MFRC522::VersionReg);
  if (version == 0x00 || version == 0xFF) {
    showStatus(STATUS_ERROR, "RC522 not found!");
    Serial.println("RC522 initialization failed");
    return;
  }
  
  Serial.println("RC522 initialized successfully");
  
  // Initialize WiFi
  handleWiFiConnection();
}

void handleBootButton() {
  bool currentBootState = digitalRead(BOOT_PIN) == LOW;
  
  if (currentBootState && !bootPressed) {
    bootPressed = true;
    bootPressTime = millis();
  } else if (!currentBootState && bootPressed) {
    bootPressed = false;
    if (millis() - bootPressTime > bootPressDelay) {
      // Long press - enter config mode
      enterConfigMode();
    }
  }
}

void enterConfigMode() {
  isInConfigMode = true;
  configModeStartTime = millis();
  currentStatus = STATUS_CONFIG_MODE;
  
  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP("AttendFT_Config", "12345678");
  
  showStatus(STATUS_CONFIG_MODE, "Config Mode");
  Serial.println("Entered configuration mode");
  
  setupWebServer();
}

void handleConfigMode() {
  server.handleClient();
  
  // Exit config mode after timeout
  if (millis() - configModeStartTime > configModeTimeout) {
    exitConfigMode();
  }
}

void exitConfigMode() {
  isInConfigMode = false;
  server.stop();
  WiFi.softAPdisconnect(true);
  
  showStatus(STATUS_STARTING, "Exiting config...");
  delay(1000);
  
  handleWiFiConnection();
}

void handleNormalOperation() {
  // Check WiFi status periodically
  if (millis() - lastWiFiCheck > wifiRetryInterval) {
    if (WiFi.status() != WL_CONNECTED && wifiConnected) {
      wifiConnected = false;
      currentStatus = STATUS_WIFI_FAILED;
      showStatus(STATUS_WIFI_FAILED, "WiFi Lost");
    }
    
    if (!wifiConnected) {
      handleWiFiConnection();
    }
    
    lastWiFiCheck = millis();
  }
  
  // Handle card reading if WiFi is connected
  if (wifiConnected) {
    handleCardReading();
  }
}

void handleWiFiConnection() {
  preferences.begin("wifi", true);
  ssid = preferences.getString("ssid", "");
  password = preferences.getString("pass", "");
  serverURL = preferences.getString("url", "");
  preferences.end();

  if (ssid == "") {
    showStatus(STATUS_CONFIG_MODE, "No WiFi config");
    delay(3000);
    enterConfigMode();
    return;
  }

  currentStatus = STATUS_WIFI_CONNECTING;
  showStatus(STATUS_WIFI_CONNECTING, "Connecting WiFi...");

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid.c_str(), password.c_str());

  unsigned long startAttemptTime = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startAttemptTime < 15000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    wifiRetryCount = 0;
    currentStatus = STATUS_READY;
    
    showStatus(STATUS_WIFI_CONNECTED, "WiFi Connected!");
    delay(1000);
    
    Serial.println("\nWiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
  } else {
    wifiRetryCount++;
    currentStatus = STATUS_WIFI_FAILED;
    
    if (wifiRetryCount >= maxWifiRetries) {
      showStatus(STATUS_CONFIG_MODE, "WiFi failed - Config");
      delay(3000);
      enterConfigMode();
    } else {
      showStatus(STATUS_WIFI_FAILED, "WiFi failed - retry");
    }
  }
}

void handleCardReading() {
  if (millis() - lastReadTime < cardReadDelay) return;

  if (!mfrc522.PICC_IsNewCardPresent()) return;
  if (!mfrc522.PICC_ReadCardSerial()) return;

  String currentCardID = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) currentCardID += "0";
    currentCardID += String(mfrc522.uid.uidByte[i], HEX);
  }
  currentCardID.toUpperCase();

  // Prevent duplicate reads of the same card too quickly
  if (currentCardID == lastCardID && millis() - lastReadTime < duplicateCardDelay) {
    return;
  }

  lastCardID = currentCardID;
  lastReadTime = millis();
  
  Serial.println("Card ID: " + currentCardID);
  
  currentStatus = STATUS_CARD_PROCESSING;
  showStatus(STATUS_CARD_PROCESSING, "Processing card...");
  
  // Send to server
  bool success = sendCardData(currentCardID);
  
  if (success) {
    currentStatus = STATUS_READY;
    showStatus(STATUS_READY, "Success!");
  } else {
    showStatus(STATUS_ERROR, "Send failed!");
  }

  delay(2000);
  currentStatus = STATUS_READY;
}

bool sendCardData(String cardID) {
  if (serverURL == "") {
    Serial.println("No server URL configured");
    return false;
  }

  HTTPClient http;
  http.begin(serverURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.setTimeout(10000); // 10 second timeout
  
  String postData = "cardID=" + cardID;
  
  int httpResponseCode = http.POST(postData);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("HTTP Response: " + String(httpResponseCode));
    Serial.println("Response: " + response);
    
    // Try to parse JSON response
    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, response);
    
    if (!error) {
      String status = doc["status"];
      String message = doc["message"];
      String studentName = doc["student_name"];
      
      if (status == "success") {
        showStatus(STATUS_READY, studentName + " - OK");
        delay(2000);
        return true;
      }
    }
  }
  
  http.end();
  return httpResponseCode > 0 && httpResponseCode < 400;
}

void setupWebServer() {
  server.on("/", HTTP_GET, []() {
    String html = R"(
<!DOCTYPE html>
<html>
<head>
    <title>AttendFT Config</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        body { font-family: Arial; margin: 40px; background: #f0f0f0; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #4CAF50; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        button:hover { background: #45a049; }
        h2 { text-align: center; color: #333; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>AttendFT Configuration</h2>
        <form action='/save' method='POST'>
            <input name='ssid' placeholder='WiFi SSID' required>
            <input name='password' placeholder='WiFi Password' type='password' required>
            <input name='server' placeholder='Server URL (e.g., http://example.com/receive_card.php)' required>
            <button type='submit'>Save & Restart</button>
        </form>
    </div>
</body>
</html>
    )";
    server.send(200, "text/html", html);
  });

  server.on("/save", HTTP_POST, []() {
    ssid = server.arg("ssid");
    password = server.arg("password");
    serverURL = server.arg("server");

    preferences.begin("wifi", false);
    preferences.putString("ssid", ssid);
    preferences.putString("pass", password);
    preferences.putString("url", serverURL);
    preferences.end();

    server.send(200, "text/html", 
      "<h2>Configuration saved!</h2>"
      "<p>Device will restart in 3 seconds...</p>"
      "<script>setTimeout(() => window.close(), 3000);</script>"
    );
    
    delay(3000);
    ESP.restart();
  });

  server.begin();
  Serial.println("Web server started");
}

void updateDisplay() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  
  // Title
  display.setTextSize(2);
  display.println("ATTENDFT");
  display.setTextSize(1);
  
  // Status information
  switch (currentStatus) {
    case STATUS_STARTING:
      display.println("Starting up...");
      break;
      
    case STATUS_WIFI_CONNECTING:
      display.println("Connecting WiFi...");
      display.println(ssid);
      break;
      
    case STATUS_WIFI_CONNECTED:
      display.println("WiFi Connected!");
      display.println(WiFi.localIP());
      break;
      
    case STATUS_WIFI_FAILED:
      display.println("WiFi Failed");
      display.println("Retrying...");
      break;
      
    case STATUS_CONFIG_MODE:
      display.println("Config Mode");
      display.println("SSID: AttendFT_Config");
      display.println("IP: " + WiFi.softAPIP().toString());
      break;
      
    case STATUS_READY:
      display.println("Ready to scan");
      display.println("WiFi: " + ssid);
      display.println("IP: " + WiFi.localIP().toString());
      display.println("");
      display.println("Hold BOOT for config");
      break;
      
    case STATUS_CARD_PROCESSING:
      display.println("Processing card...");
      break;
      
    case STATUS_ERROR:
      display.println("ERROR");
      display.println("Check connections");
      break;
  }
  
  display.display();
}

void showStatus(SystemStatus status, String message) {
  currentStatus = status;
  Serial.println("Status: " + message);
  updateDisplay();
}