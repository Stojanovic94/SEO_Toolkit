// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    initializeDarkMode();
    initializeTabSystem();
    initializeFormHandling();
    initializeCopyFunctions();
    initializeExportFunctions();
    autoFocusInput();
});

// Dark Mode Toggle
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;
    
    // Check for saved preference or system preference
    const savedTheme = localStorage.getItem('theme') || 
                      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    if (savedTheme === 'dark') {
        htmlElement.setAttribute('data-theme', 'dark');
        darkModeToggle.classList.add('active');
    }
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            darkModeToggle.classList.toggle('active');
        });
    }
}

// Tab System
function initializeTabSystem() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });
}

// Form Handling
function initializeFormHandling() {
    const metaForm = document.getElementById('metaForm');
    if (metaForm) {
        metaForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
            submitBtn.disabled = true;
            
            // Reset after submission
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1000);
        });
    }
}

// Copy Functions
function initializeCopyFunctions() {
    const copyRawBtn = document.getElementById('copyRaw');
    if (copyRawBtn) {
        copyRawBtn.addEventListener('click', copyRawMetaData);
    }
}

function copyRawMetaData() {
    const rawMeta = document.getElementById('rawMeta');
    if (!rawMeta) return;
    
    const text = rawMeta.textContent;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Raw meta data copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy data', 'error');
    });
}

function copyURL() {
    const urlElement = document.querySelector('.url-text');
    if (!urlElement) return;
    
    const url = urlElement.textContent;
    navigator.clipboard.writeText(url).then(() => {
        showToast('URL copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy URL', 'error');
    });
}

// Export Functions
function exportJSON() {
    const rawMeta = document.getElementById('rawMeta');
    const urlElement = document.querySelector('.url-text');
    
    if (!rawMeta || !urlElement) return;
    
    const data = JSON.parse(rawMeta.textContent);
    const url = urlElement.textContent;
    const timestamp = new Date().toISOString();
    
    const exportData = {
        url: url,
        timestamp: timestamp,
        seoAnalysis: data
    };
    
    const jsonString = JSON.stringify(exportData, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `seo-analysis-${Date.now()}.json`;
    link.click();
    
    showToast('JSON file downloaded successfully!', 'success');
}

// Toast Notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = '';
    
    // Set background color based on type
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b'
    };
    
    toast.style.backgroundColor = colors[type] || colors.success;
    toast.classList.add('show');
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Auto-focus URL input
function autoFocusInput() {
    const urlInput = document.getElementById('url');
    if (urlInput && !urlInput.value) {
        urlInput.focus();
    }
}

// Helper function to get SEO recommendations
function getSEORecommendations(seoScore) {
    const recommendations = [];
    
    if (seoScore < 60) {
        recommendations.push('Add or improve your meta description (120-160 characters)');
    }
    if (seoScore < 70) {
        recommendations.push('Implement Open Graph tags for social media sharing');
    }
    if (seoScore < 80) {
        recommendations.push('Add JSON-LD structured data');
    }
    
    return recommendations;
}