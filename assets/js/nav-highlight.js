document.addEventListener('DOMContentLoaded', function() {
    // Get current page filename
    const currentPage = window.location.pathname.split('/').pop();
    
    // Map pages to nav IDs
    const pageToNavId = {
        'admin-index.php': 'dashboard',
        'admin-manage.php': 'dashboard',
        'admin-reports.php': 'dashboard',
        'admin-locations.php': 'dashboard',
        'admin-attendance-records.php': 'attendance',
        'admin-attendance-records-more.php': 'attendance',
        'admin-appointment.php': 'appointment',
        'admin-announcement.php': 'announcement',
        'admin-faculty.php': 'faculty',
        'admin-schedule.php': 'schedule',
        'admin-sections.php': 'sections',
        'admin-student.php': 'student',
        'admin-subjects.php': 'subjects',
        'admin-manage.php': 'admin'
    };
    
    // Find the matching nav item
    const activeNavId = pageToNavId[currentPage];
    
    // Set active class if match found
    if (activeNavId) {
        const activeButton = document.querySelector(`.nav-button[data-nav-id="${activeNavId}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
    }
    
    // Click handler for manual selection
    const navButtons = document.querySelectorAll('.nav-button');
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            navButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});