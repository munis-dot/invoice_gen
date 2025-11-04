// Function to download the element as PDF
function downloadElementAsPDF(filename = 'invoice.pdf') {
  const element = document.querySelector('.invoice-template');
  if (!element) {
    console.error('Element with class .invoice-template not found');
    return;
  }

  // Use html2canvas to render the element as canvas
  html2canvas(element, {
    scale: 2, // Higher resolution
    useCORS: true,
    allowTaint: true
  }).then(canvas => {
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jspdf.jsPDF('p', 'mm', 'a4'); // Portrait, mm, A4
    const imgWidth = 210;
    const pageHeight = 295;
    const imgHeight = (canvas.height * imgWidth) / canvas.width;
    let heightLeft = imgHeight;
    let position = 0;

    // Add first page
    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
    heightLeft -= pageHeight;

    // Add additional pages if content overflows
    while (heightLeft >= 0) {
      position = heightLeft - imgHeight;
      pdf.addPage();
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;
    }

    pdf.save(filename);
  }).catch(err => console.error('Error generating PDF:', err));
}

// Function to email the element (generates PDF download first, then opens mailto: for manual attachment)
function emailElementAsPDF(toEmail = '', subject = 'Invoice') {
  const filename = 'invoice-temp.pdf';
  downloadElementAsPDF(filename); // Triggers download; user attaches manually

  // Open email client
  const body = encodeURIComponent('Please find the attached invoice PDF.');
  const mailtoLink = `mailto:${toEmail}?subject=${encodeURIComponent(subject)}&body=${body}`;
  window.location.href = mailtoLink;
}

// Function to print the element
function printElement() {
  const element = document.querySelector('.invoice-template');
  if (!element) {
    console.error('Element with class .invoice-template not found');
    return;
  }

  // Clone to new window for isolated print
  const printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write(`
    <html>
      <head>
        <title>Invoice Print</title>
        <link rel="stylesheet" href="D:\php\htdocs\invoice_gen\frontend\assets\css\invoice.css">
      </head>
      <body>${element.innerHTML}</body>
    </html>
  `);
  printWindow.document.close();
  // Alternative method: Fetch external CSS and inject as inline <style>
  const cssPath = 'assets/css/invoice.css'; // Replace with your actual CSS path
  fetch(cssPath)
    .then(response => response.text())
    .then(css => {
      const styleElement = printWindow.document.createElement('style');
      styleElement.textContent = css;
      printWindow.document.head.appendChild(styleElement);
      printWindow.focus();
      printWindow.print();
      printWindow.close(); // Closes after print dialog
    })
    .catch(err => {
      console.error('Error loading CSS:', err);
      // Fallback: Proceed without external CSS
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    });
}

// Expose functions globally for button onclick
window.downloadElementAsPDF = downloadElementAsPDF;
window.emailElementAsPDF = emailElementAsPDF;
window.printElement = printElement;