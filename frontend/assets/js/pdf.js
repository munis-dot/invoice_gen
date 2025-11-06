// Function to download the element as PDF
function downloadElementAsPDF(filename = 'invoice.pdf') {
  const element = document.querySelector('.panel');
  if (!element) {
    console.error('Element with class .panel not found');
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
// function emailElementAsPDF(toEmail = '', subject = 'Invoice') {
//   const filename = 'invoice-temp.pdf';
//   downloadElementAsPDF(filename); // Triggers download; user attaches manually

//   // Open email client
//   const body = encodeURIComponent('Please find the attached invoice PDF.');
//   const mailtoLink = `mailto:${toEmail}?subject=${encodeURIComponent(subject)}&body=${body}`;
//   window.location.href = mailtoLink;
// }

function emailElementAsPDF(recipientEmail, subject) {
    const element = document.querySelector('.panel');
    if (!element) {
        alert('Invoice element not found!');
        return;
    }

    // Generate PDF as blob
    html2pdf()
  .from(element)
  .set({
    margin: 1,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
  })
  .toPdf()
  .output('blob')
  .then(pdfBlob => {
    const pdfFile = new File([pdfBlob], 'invoice.pdf', { type: 'application/pdf' });
    const formData = new FormData();
    formData.append('pdf', pdfFile);
    formData.append('email', recipientEmail);
    formData.append('subject', subject);
    formData.append('body', 'Please find the attached invoice PDF.');

    return fetch('./utils/email_invoice.php', { method: 'POST', body: formData });
  })
  .then(res => res.json())
  .then(data => console.log(data))
  .catch(console.error);
}
// Function to print the element
function printElement() {
  const element = document.querySelector('.panel');
  if (!element) {
    console.error('Element with class .panel not found');
    return;
  }

  // Clone to new window for isolated print
  const printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write(`
    <html>
      <head>
        <title>Invoice Print</title>
        <link rel="stylesheet" href="D:\php\htdocs\invoice_gen\frontend\assets\css\invoice_builder.css">
      </head>
      <style>
      *:not(.items-table th, .items-table td) {
    border:none !important;    
}
      </style>
      <body>${element.innerHTML}</body>
    </html>
  `);
  printWindow.document.close();
  // Alternative method: Fetch external CSS and inject as inline <style>
  const cssPath = 'assets/css/invoice_builder.css'; // Replace with your actual CSS path
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