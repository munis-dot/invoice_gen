// Define loadPage as a global function so other modules can use it
window.loadPage = function(page, params = {}) {
  let renderUrl = `render.php?page=${page}`;
  let browserUrl = `?page=${page}`;

  // Add all parameters to both URLs
  Object.entries(params).forEach(([key, value]) => {
    renderUrl += `&${key}=${encodeURIComponent(value)}`;
    browserUrl += `&${key}=${encodeURIComponent(value)}`;
  });

  fetch(renderUrl)
    .then(res => {
      if (!res.ok) throw new Error("Page not found");
      window.history.pushState({ page, params }, '', browserUrl);
      return res.text();
    })
    .then(html => {
      document.getElementById("app-content").innerHTML = html;
      initializePage(page);
    })
    .catch(err => {
      console.error(err);
      document.getElementById("app-content").innerHTML =
        "<h3>⚠️ Failed to load page.</h3>";
    });
}

// Initialize page-specific scripts
function initializePage(page) {
  // Clear any previously loaded scripts
  const oldScripts = document.querySelectorAll('script[data-dynamic]');
  oldScripts.forEach(script => script.remove());

  // Map of pages to their corresponding script files
  const pageScripts = {
    'customers/customer_list': 'assets/js/customer_list.js',
    'customers/customer_add': 'assets/js/customer_add.js',
    'products/list': 'assets/js/product_list.js',
    'products/create': 'assets/js/product_form.js',
    'products/edit': 'assets/js/product_form.js',
    'products/view': 'assets/js/product_view.js',
    'transactions/transaction_list': 'assets/js/transaction_list.js',
    'transactions/transaction_add': 'assets/js/transaction_add.js',
    'transactions/view': 'assets/js/transaction_view.js'
  };

  // Load the appropriate script for the page
  if (pageScripts[page]) {
    const script = document.createElement('script');
    script.src = pageScripts[page];
    script.type = 'module';
    script.setAttribute('data-dynamic', 'true');
    document.body.appendChild(script);
  }

  // Always bind customer events for navigation
  bindCustomerEvents();
}

// Handle navigation clicks
document.addEventListener("click", (e) => {
  if (e.target.matches("[data-link]")) {
    e.preventDefault();
    let page = e.target.getAttribute("data-link");
    let params = {};

    // Check if the page contains query parameters
    if (page.includes('?')) {
      const [basePath, queryString] = page.split('?');
      page = basePath;
      
      // Parse query parameters into params object
      const urlParams = new URLSearchParams(queryString);
      urlParams.forEach((value, key) => {
        params[key] = value;
      });
    }

    // Special handling for customers
    if (page === 'customers') {
      loadPage('customers/customer_list', params);
    } else {
      loadPage(page, params);
    }
  }
});

// Handle login form submission
document.addEventListener("submit", (e) => {
  if (e.target.matches("#login-form")) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('modules/login_action.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(data => {
      try {
        const result = JSON.parse(data);
        if (result.success) {
          window.location.href = 'index.php';
        } else {
          const errorDiv = document.getElementById('login-error');
          if (errorDiv) {
            errorDiv.textContent = result.message || 'Login failed. Please try again.';
            errorDiv.style.display = 'block';
          }
        }
      } catch (e) {
        console.error('Error parsing response:', e);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      const errorDiv = document.getElementById('login-error');
      if (errorDiv) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.style.display = 'block';
      }
    });
  }
});

function loadPage(page, params = {}) {
  let renderUrl = `render.php?page=${page}`;
  let browserUrl = `?page=${page}`;

  if (params.id) {
    renderUrl += `&id=${params.id}`;
    browserUrl += `&id=${params.id}`;
  }

  fetch(renderUrl)
    .then(res => {
      if (!res.ok) throw new Error("Page not found");
      // ✅ Update the browser URL (without reload)
      window.history.pushState({ page, params }, '', browserUrl);
      return res.text();
    })
    .then(html => {
      document.getElementById("app-content").innerHTML = html;
      bindCustomerEvents(); // your existing binding logic
    })
    .catch(err => {
      console.error(err);
      document.getElementById("app-content").innerHTML =
        "<h3>⚠️ Failed to load page.</h3>";
    });
}

window.addEventListener("popstate", (event) => {
  if (event.state) {
    loadPage(event.state.page, event.state.params);
  } else {
    loadPage('dashboard');
  }
});


function bindCustomerEvents() {
  // Table row double-click to view
  document.querySelectorAll('.customer-row').forEach(row => {
    row.ondblclick = () => {
      const id = row.getAttribute('data-id');
      loadPage('customers/customer_view', { id });
    };
  });

  // Edit button
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.onclick = (e) => {
      e.stopPropagation();
      const id = btn.getAttribute('data-id');
      loadPage('customers/customer_view', { id });
    };
  });

  // Delete button
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.onclick = async (e) => {
      e.stopPropagation();
      const id = btn.getAttribute('data-id');
      if (!confirm('Delete this customer?')) return;
      const res = await fetch('frontend/modules/customers/api_client.php', {
        method: 'POST',
        body: JSON.stringify({
          endpoint: `backend/public/index.php/api/customers/${id}`,
          method: 'DELETE'
        })
      });
      const result = await res.json();
      alert(result.message || 'Customer deleted!');
      loadPage('customers');
    };
  });

  // Back button in view
  const backBtn = document.getElementById('back-to-list');
  if (backBtn) {
    backBtn.onclick = () => loadPage('customers');
  }

  // Add customer form
  const customerForm = document.getElementById('customerForm');
  if (customerForm) {
    customerForm.onsubmit = async (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(customerForm));
      const res = await fetch('frontend/modules/customers/api_client.php', {
        method: 'POST',
        body: JSON.stringify({
          endpoint: 'backend/public/index.php/api/customers',
          method: 'POST',
          data
        })
      });
      const result = await res.json();
      alert(result.message || 'Customer added!');
      loadPage('customers');
    };
  }

  // Update customer form
  const updateForm = document.getElementById('updateForm');
  if (updateForm) {
    updateForm.onsubmit = async (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(updateForm));
      const res = await fetch('frontend/modules/customers/api_client.php', {
        method: 'POST',
        body: JSON.stringify({
          endpoint: `backend/public/index.php/api/customers/${data.id}`,
          method: 'PUT',
          data
        })
      });
      const result = await res.json();
      alert(result.message || 'Customer updated!');
      loadPage('customers');
    };
  }

  // // Bulk upload form
  // const uploadForm = document.getElementById('uploadForm');
  // if (uploadForm) {
  //   uploadForm.onsubmit = async (e) => {
  //     e.preventDefault();
  //     const formData = new FormData(uploadForm);
  //     const res = await fetch('backend/public/index.php/api/customers/bulk', {
  //       method: 'POST',
  //       body: formData
  //     });
  //     const result = await res.json();
  //     alert(result.message || 'Bulk upload complete!');
  //     loadPage('customers');
  //   };
  // }
}

// Initialize on page load
window.addEventListener('DOMContentLoaded', () => {
  // Load the initial page based on URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const page = urlParams.get('page');
  const id = urlParams.get('id');
  
  if (page) {
    loadPage(page, { id });
  } else {
    loadPage('dashboard');
  }
  
  // Load common scripts that should be available everywhere
  const commonScripts = [
    'assets/js/form_handler.js',
    'assets/js/list_handler.js'
  ];
  
  commonScripts.forEach(scriptSrc => {
    const script = document.createElement('script');
    script.src = scriptSrc;
    script.type = 'module';
    script.setAttribute('data-dynamic', 'true');
    document.body.appendChild(script);
  });
});

// Handle browser back/forward buttons
window.addEventListener('popstate', (event) => {
  if (event.state) {
    loadPage(event.state.page, event.state.params);
  } else {
    // If no state exists, load the default page or handle accordingly
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    const id = urlParams.get('id');
    
    if (page) {
      loadPage(page, { id });
    }
  }
});
