function fetchAttendanceData(searchTerm = '') {
    const formData = new FormData();
    formData.append('search', searchTerm);

    fetch('get_attendance_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const attendanceTableBody = document.getElementById('attendanceTableBody');
        
        if (!attendanceTableBody) {
            console.warn('Attendance table body not found');
            return;
        }

        attendanceTableBody.innerHTML = '';

        if (data.error) {
            console.error('Server error:', data.error);
            attendanceTableBody.innerHTML = '<tr><td colspan="7">Error loading data</td></tr>';
            return;
        }

        if (data.length === 0) {
            attendanceTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; font-style: italic;">No attendance records found</td></tr>';
            return;
        }

        data.forEach(attendance => {
            const row = document.createElement('tr');
            const statusColor = attendance.status === 'Completed' ? 'green' : 'orange';
            
            row.innerHTML = `
                <td>${attendance.id}</td>
                <td>${attendance.full_name}</td>
                <td>${attendance.student_id}</td>
                <td>${attendance.entry_time}</td>
                <td>${attendance.exit_time}</td>
                <td>${attendance.card_id}</td>
                <td><span style="color: ${statusColor}; font-weight: bold;">${attendance.status}</span></td>
            `;
            attendanceTableBody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error fetching attendance data:', error);
        const attendanceTableBody = document.getElementById('attendanceTableBody');
        if (attendanceTableBody) {
            attendanceTableBody.innerHTML = '<tr><td colspan="7">Error loading data</td></tr>';
        }
    });
}

function fetchStudentData(searchTerm = '') {
    const formData = new FormData();
    formData.append('search', searchTerm);

    fetch('get_student_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const studentTableBody = document.getElementById('studentTableBody');
        
        if (!studentTableBody) {
            console.warn('Student table body not found');
            return;
        }

        studentTableBody.innerHTML = '';

        if (data.error) {
            console.error('Server error:', data.error);
            studentTableBody.innerHTML = '<tr><td colspan="5">Error loading data</td></tr>';
            return;
        }

        if (data.length === 0) {
            studentTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; font-style: italic;">No students found</td></tr>';
            return;
        }

        data.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.id}</td>
                <td>${student.full_name}</td>
                <td>${student.student_id}</td>
                <td>${student.card_id}</td>
                <td>${student.created_at}</td>
            `;
            studentTableBody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error fetching student data:', error);
        const studentTableBody = document.getElementById('studentTableBody');
        if (studentTableBody) {
            studentTableBody.innerHTML = '<tr><td colspan="5">Error loading data</td></tr>';
        }
    });
}

// Initialize data on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initial load of student data
    fetchStudentData();
    
    // Initial load of attendance data
    fetchAttendanceData();
});

// Handle search form submission
const searchForm = document.getElementById('searchForm');
if (searchForm) {
    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const searchTerm = document.getElementById('searchInput').value.trim();
        fetchAttendanceData(searchTerm);
        fetchStudentData(searchTerm);
    });
}

// Real-time updates every 30 seconds (only if no search is active)
setInterval(() => {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput || !searchInput.value.trim()) {
        fetchAttendanceData();
        // Don't auto-refresh student data as it changes less frequently
    }
}, 30000);

// Add notification for real-time updates
function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            display: none;
        `;
        document.body.appendChild(notification);
    }

    // Set notification style based on type
    const colors = {
        'info': '#3498db',
        'success': '#27ae60',
        'warning': '#f39c12',
        'error': '#e74c3c'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    notification.textContent = message;
    notification.style.display = 'block';

    // Hide after 3 seconds
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}