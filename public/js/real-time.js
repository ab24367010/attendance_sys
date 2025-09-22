function fetchAttendanceData(searchTerm = '') {
    const formData = new FormData();
    formData.append('search', searchTerm);

    fetch('get_attendance_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const attendanceTableBody = document.getElementById('attendanceTableBody');
        attendanceTableBody.innerHTML = '';

        data.forEach(attendance => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${attendance.id}</td>
                <td>${attendance.full_name}</td>
                <td>${attendance.entry_time}</td>
                <td>${attendance.exit_time}</td>
                <td>${attendance.card_id}</td>
            `;
            attendanceTableBody.appendChild(row);
        });
    })
    .catch(error => console.error('Алдаа гарлаа:', error));
}

function fetchStudentData(searchTerm = '') {
    const formData = new FormData();
    formData.append('search', searchTerm);

    fetch('get_student_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const studentTableBody = document.getElementById('studentTableBody');
        studentTableBody.innerHTML = '';

        data.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.id}</td>
                <td>${student.full_name}</td>
                <td>${student.student_id}</td>
                <td>${student.card_id}</td>
            `;
            studentTableBody.appendChild(row);
        });
    })
    .catch(error => console.error('Алдаа гарлаа:', error));
}

// Эхний удаа оюутны мэдээллийг авч дэлгэц дээр гаргах
fetchStudentData();

// Хайлт хийх үйлдлийг барих
const searchForm = document.getElementById('searchForm');
searchForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const searchTerm = document.getElementById('searchInput').value;
    fetchAttendanceData(searchTerm);
    fetchStudentData(searchTerm);
});

// 30 секунд тутамд ирцийн мэдээллийг авах
setInterval(() => {
    fetchAttendanceData();
}, 30000);