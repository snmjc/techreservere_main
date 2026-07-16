import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
import * as XLSX from 'xlsx';

export function exportRowsToCsv(fileName, rows) {
  if (!Array.isArray(rows) || rows.length === 0) {
    return;
  }

  const worksheet = XLSX.utils.json_to_sheet(rows);
  const csvContent = XLSX.utils.sheet_to_csv(worksheet);
  triggerDownload(`${fileName}.csv`, 'text/csv;charset=utf-8;', csvContent);
}

export function exportRowsToExcel(fileName, rows, sheetName = 'Export') {
  if (!Array.isArray(rows) || rows.length === 0) {
    return;
  }

  const workbook = XLSX.utils.book_new();
  const worksheet = XLSX.utils.json_to_sheet(rows);
  XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
  XLSX.writeFile(workbook, `${fileName}.xlsx`);
}

export async function exportElementToPdf(fileName, element) {
  if (!element) {
    return;
  }

  const canvas = await html2canvas(element, {
    scale: 1.5,
    useCORS: true,
    backgroundColor: '#ffffff',
  });

  const imageData = canvas.toDataURL('image/png');
  const pdf = new jsPDF('p', 'mm', 'a4');
  const pageWidth = pdf.internal.pageSize.getWidth();
  const pageHeight = pdf.internal.pageSize.getHeight();
  const ratio = Math.min(pageWidth / canvas.width, pageHeight / canvas.height);
  const renderWidth = canvas.width * ratio;
  const renderHeight = canvas.height * ratio;

  pdf.addImage(imageData, 'PNG', 0, 0, renderWidth, renderHeight);
  pdf.save(`${fileName}.pdf`);
}

export function printElement(element, title = 'TechReserve Export') {
  if (!element || typeof window === 'undefined') {
    return;
  }

  const printWindow = window.open('', '_blank', 'width=1200,height=900');
  if (!printWindow) {
    return;
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>${title}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 24px; color: #183b2b; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #d6e3db; padding: 8px; text-align: left; font-size: 12px; }
          th { background: #edf5f0; }
        </style>
      </head>
      <body>${element.outerHTML}</body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function triggerDownload(fileName, mimeType, content) {
  if (typeof window === 'undefined') {
    return;
  }

  const blob = new Blob([content], { type: mimeType });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = fileName;
  anchor.click();
  URL.revokeObjectURL(url);
}
