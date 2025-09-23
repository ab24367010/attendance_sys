// public/js/dashboard.js

// Modal functionality
const modal = document.getElementById('editModal');
const closeBtn = document.querySelector('.close');

function openEditModal(attendanceId, entryTime, exitTime) {
    const modal = document.getElementById('editModal');
    if (!modal) return;
    
    document.getElementById('editAttendanceId').value = attendanceId;
    
    // Convert datetime to local datetime-local format
    if (entryTime) {
        const entryDate = new Date(entryTime);
        document.getElementById('editEntryTime').value = formatDateTimeLocal(entryDate);
    }
    
    if (exitTime) {
        const exitDate = new Date(exitTime);
        document.getElementById('editExitTime').value = formatDateTimeLocal(exitDate);
    } else {
        document.getElementById('editExitTime').value = '';
    }
    
    document.getElementById('editReason').value = '';
    modal.style.display = 'block';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}

// Close modal when clicking X
if (closeBtn) {
    closeBtn.onclick = closeEditModal;
}

// Handle edit form submission
const editForm = document.getElementById('editAttendanceForm');
if (editForm) {
    editForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('auth/edit_attendance.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Attendance record updated successfully!', 'success');
                closeEditModal();
                // Refresh the page to show updated data
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            showNotification('Error updating attendance record', 'error');
            console.error('Edit error:', error);
        }
    });
}

// Enhanced notification function
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
            padding: 15px 20px;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        `;
        document.body.appendChild(notification);
    }
    
    // Set message and style based on type
    notification.textContent = message;
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            notification.style.backgroundColor = '#f44336';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ff9800';
            break;
        default:
            notification.style.backgroundColor = '#2196F3';
    }
    
    // Show notification
    notification.style.opacity = '1';
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
    }, 3000);
}

// Fetch and display initial data
document.addEventListener('DOMContentLoaded', function() {
    // Assuming fetchAttendanceData and fetchStudentData are defined in real-time.js
    if (typeof fetchAttendanceData === 'function') {
        fetchAttendanceData();
    }
    if (typeof fetchStudentData === 'function') {
        fetchStudentData();
    }
});


// Initial data fetch on page load
document.addEventListener('DOMContentLoaded', function() {
    fetchStudentData();
    fetchAttendanceData();
});

// Handle search form submission
const searchForm = document.getElementById('searchForm');
if (searchForm) {
    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const searchTerm = document.getElementById('searchInput').value.trim();
        fetchStudentData(searchTerm);
        fetchAttendanceData(searchTerm);
    });
}

// Periodic refresh every 5 minutes (only if no search is active)
setInterval(() => {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput || !searchInput.value.trim()) {
        fetchStudentData();
        fetchAttendanceData();
    }
}, 300000); // 300,000 ms = 5 minutes

// Add notification for dashboard actions
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
            padding: 15px 20px;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        `;
        document.body.appendChild(notification);
    }
    
    // Set message and style based on type
    notification.textContent = message;
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            notification.style.backgroundColor = '#f44336';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ff9800';
            break;
        default:
            notification.style.backgroundColor = '#2196F3';
    }
    
    // Show notification
    notification.style.opacity = '1';
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
    }, 3000);
}

// Fetch and display initial data
document.addEventListener('DOMContentLoaded', function() {
    fetchStudentData();
    fetchAttendanceData();
});