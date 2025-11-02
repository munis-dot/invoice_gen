// Define loadPage as a global function so other modules can use it
window.loadPage = function (page, params = {}) {
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
async function initializePage(page) {
  // Clear any previously loaded scripts
  const oldScripts = document.querySelectorAll('script[data-dynamic]');
  oldScripts.forEach(script => script.remove());

  // Map of pages to their corresponding script files
  const pageScripts = {
    'customers/customer_list': 'assets/js/customer_list.js',
    'customers/customer_add': 'assets/js/customer_add.js',
    'products/list': 'assets/js/product_list.js',
    'products/create': 'assets/js/product_form.js',
    'transactions/transaction_list': 'assets/js/transaction_list.js',
    'transactions/transaction_add': 'assets/js/transaction_add.js',
  };

  // Load the appropriate script for the page
  if (pageScripts[page]) {
    try {
      const scriptSrc = pageScripts[page];
      const scriptPath = new URL(scriptSrc, window.location.href).href;
      
      // Import the module directly
      const module = await import(scriptPath);
      console.log('Module loaded:', module);
      
      if (module.default && typeof module.default === 'function') {
        try {
          new module.default();
          console.log('Module initialized successfully');
        } catch (error) {
          console.error('Error initializing module:', error);
        }
      } else {
        console.warn('No default export found in module:', scriptPath);
      }
    } catch (error) {
      console.error('Error loading page script:', error);
    }
  }

}

// Handle navigation clicks
document.addEventListener("click", (e) => {
  if (e.target.matches("[data-link]")) {
    e.preventDefault();
    console.log("first")
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


// Initialize on page load
window.addEventListener('DOMContentLoaded', async () => {
  // Load the initial page based on URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const page = urlParams.get('page');
  const id = urlParams.get('id');

  // Load common scripts that should be available everywhere
  const commonScripts = [
    'assets/js/form_handler.js',
    'assets/js/list_handler.js',
    'assets/js/pdf.js'
  ];

  // Load common scripts sequentially and wait for them to complete
  try {
    for (const scriptSrc of commonScripts) {
      await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = scriptSrc;
        script.type = 'module';
        script.setAttribute('data-dynamic', 'true');
        script.onload = resolve;
        script.onerror = reject;
        document.body.appendChild(script);
      });
    }

    // After common scripts are loaded, initialize the page
    if (page) {
      loadPage(page, { id });
    } else {
      loadPage('dashboard');
    }
  } catch (error) {
    console.error('Error loading common scripts:', error);
  }
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
