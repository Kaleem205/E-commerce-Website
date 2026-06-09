// Auto-hide alerts after 4 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
});



// ==========================================
// LIVE SEARCH AUTOCOMPLETE
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('live-search-input');
    const searchForm = document.getElementById('live-search-form');

    if (searchInput && searchForm) {
        // Create the dropdown container dynamically
        const dropdown = document.createElement('div');
        dropdown.className = 'search-dropdown';
        searchForm.appendChild(dropdown);

        // Listen for typing
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length > 0) {
                // Fetch data from our new PHP API
                fetch('/shopping_system/ajax/live_search.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        dropdown.innerHTML = ''; // Clear old results
                        
                        if (data.length > 0) {
                            data.forEach(item => {
                                const link = document.createElement('a');
                                link.href = '/shopping_system/product.php?id=' + item.id;
                                link.className = 'search-dropdown-item';
                                link.innerHTML = `
                                    <img src="/shopping_system/uploads/products/${item.image}" onerror="this.src='/shopping_system/uploads/products/default.jpg'">
                                    <span>${item.name}</span>
                                `;
                                dropdown.appendChild(link);
                            });
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.innerHTML = '<div class="search-dropdown-empty">No products found</div>';
                            dropdown.style.display = 'block';
                        }
                    });
            } else {
                dropdown.style.display = 'none'; // Hide if input is empty
            }
        });

        // Hide dropdown when clicking anywhere outside of it
        document.addEventListener('click', function(e) {
            if (!searchForm.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }
});


// ==========================================
// ACCOUNT TABS LOGIC
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Add active class to the clicked button
            btn.classList.add('active');

            // Show the corresponding content
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });
});



// ==========================================
// AJAX ADD TO CART & TOAST NOTIFICATIONS
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Create Toast Container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    // Function to show Toast
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span>${type === 'success' ? '✅' : '❌'} ${message}</span>
        `;
        toastContainer.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Listen for Add to Cart clicks
    document.querySelectorAll('.ajax-add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const originalText = this.innerText;

            // Loading state
            this.innerText = 'Adding...';
            this.disabled = true;

            fetch('/shopping_system/ajax/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                this.innerText = originalText;
                this.disabled = false;

                if (data.status === 'unauthorized') {
                    // Redirect to login if not logged in
                    window.location.href = '/shopping_system/login.php';
                } else if (data.status === 'success') {
                    // Update header cart count
                    const cartBadge = document.querySelector('.cart-count');
                    if (cartBadge) {
                        cartBadge.innerText = data.cart_count;
                        // Add a little bounce animation to the badge
                        cartBadge.style.transform = 'scale(1.5)';
                        setTimeout(() => cartBadge.style.transform = 'scale(1)', 200);
                    }
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                this.innerText = originalText;
                this.disabled = false;
                showToast('Something went wrong. Please try again.', 'error');
            });
        });
    });
});