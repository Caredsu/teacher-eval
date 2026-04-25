/**
 * PDF Export Utilities
 * Handles PDF export functionality for all pages using jsPDF + html2canvas
 */

// Load jsPDF and html2canvas from CDN
function loadPDFLibraries() {
    if (typeof jsPDF === 'undefined') {
        const script1 = document.createElement('script');
        script1.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        document.head.appendChild(script1);
    }
}

/**
 * Generic PDF export function
 */
async function exportTableToPDF(exportType, title) {
    try {
        // Show loading indicator
        Swal.fire({
            title: 'Generating PDF...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: async (modal) => {
                Swal.showLoading();
                
                // Fetch data from API
                const response = await fetch(`/teacher-eval/api/export-pdf.php?type=${exportType}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                const text = await response.text();
                let result;
                
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response Text:', text.substring(0, 500));
                    throw new Error('Invalid response from server. Please refresh the page and try again.');
                }
                
                if (!result.success) {
                    Swal.fire('Error', result.message || 'Failed to export data', 'error');
                    return;
                }
                
                const data = result.data;
                
                    // Create PDF using html2pdf
                loadPDFLibraries();
                
                setTimeout(() => {
                    const element = document.createElement('div');
                    element.style.padding = '30px';
                    element.style.fontFamily = '"Segoe UI", Arial, sans-serif';
                    element.style.maxWidth = '900px';
                    element.style.margin = '0 auto';
                    element.style.lineHeight = '1.6';
                    
                    // ===== HEADER SECTION =====
                    let html = `
                        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e0e0e0;">
                            <!-- Logo -->
                            <div style="margin-bottom: 15px;">
                                <img src="../assets/img/2.png" alt="Fullbright College Logo" style="height: 70px; width: 70px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">  
                            </div>
                            
                            <!-- School Name -->
                            <h1 style="margin: 15px 0 5px 0; color: #1a1a1a !important; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; opacity: 1;">FULLBRIGHT COLLEGE INC</h1>
                            
                            <!-- Address and Contact -->
                            <p style="margin: 8px 0; color: #333 !important; font-size: 13px; opacity: 1;">KM 5 National Highway, San Jose, Puerto Princesa, Philippines, 5300</p>
                            <p style="margin: 5px 0; color: #333 !important; font-size: 13px; opacity: 1;">Email: fullbrightcollege@yahoo.com</p>
                        </div>
                        
                        <!-- ===== TITLE SECTION ===== -->
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="margin: 0 0 5px 0; color: #1a1a1a !important; font-size: 28px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; opacity: 1;">${data.title}</h2>
                            <p style="margin: 8px 0 0 0; color: #666 !important; font-size: 12px; opacity: 0.9;">Generated on: ${new Date().toLocaleString()}</p>
                        </div>
                    `;
                    
                    // ===== SUMMARY/STATISTICS SECTION =====
                    if (data.summary && Object.keys(data.summary).length > 0) {
                        html += '<div style="margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
                        
                        const summaryKeys = Object.keys(data.summary);
                        summaryKeys.forEach(key => {
                            const value = data.summary[key];
                            const bgColor = ['#f5f7fa', '#eff6ff', '#f0fdf4'][summaryKeys.indexOf(key) % 3];
                            const accentColor = ['#667eea', '#3b82f6', '#10b981'][summaryKeys.indexOf(key) % 3];
                            
                            html += `
                                <div style="background: ${bgColor}; border-left: 4px solid ${accentColor}; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <p style="margin: 0; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">${key}</p>
                                    <p style="margin: 8px 0 0 0; font-size: 24px; font-weight: 700; color: ${accentColor};">${value}</p>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                    }
                    
                    // ===== DATA TABLE SECTION =====
                    if (data.rows && data.rows.length > 0) {
                        html += '<div style="margin-top: 30px;">';
                        html += '<h3 style="color: #1a1a1a !important; margin: 0 0 15px 0; font-size: 16px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; opacity: 1;">Detailed Report</h3>';
                        
                        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">';
                        
                        // Table header
                        const headers = Object.keys(data.rows[0]);
                        html += '<thead><tr style="background: #2c3e50; color: white; font-weight: 600;">';
                        headers.forEach((header, idx) => {
                            const align = header === 'Status' ? 'center' : (header === 'Date Added' ? 'right' : 'left');
                            html += `<th style="padding: 14px 10px; text-align: ${align}; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">${header}</th>`;
                        });
                        html += '</tr></thead>';
                        
                        // Table rows
                        html += '<tbody>';
                        data.rows.forEach((row, index) => {
                            const bgColor = index % 2 === 0 ? '#ffffff' : '#f8f9fa';
                            html += `<tr style="background: ${bgColor}; border-bottom: 1px solid #e8e8e8;">`;
                            headers.forEach(header => {
                                const align = header === 'Status' ? 'center' : (header === 'Date Added' ? 'right' : 'left');
                                let cellContent = row[header] || '-';
                                
                                // Format status as badge
                                if (header === 'Status') {
                                    const statusLower = cellContent.toLowerCase();
                                    const isActive = statusLower.includes('active');
                                    const bgColor = isActive ? '#d4edda' : '#f8d7da';
                                    const textColor = isActive ? '#155724' : '#721c24';
                                    cellContent = `<span style="background: ${bgColor}; color: ${textColor}; padding: 4px 12px; border-radius: 4px; font-weight: 500; font-size: 11px;">${cellContent}</span>`;
                                }
                                
                                html += `<td style="padding: 12px 10px; text-align: ${align}; color: #1a1a1a; border-right: 1px solid #e8e8e8;">${cellContent}</td>`;
                            });
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                        html += '</div>';
                    }
                    
                    // ===== TOP RATED TEACHERS SECTION =====
                    if (data.top_rated && data.top_rated.length > 0) {
                        html += '<div style="margin-top: 30px;">';
                        html += '<h3 style="color: #1a1a1a !important; margin: 0 0 15px 0; font-size: 16px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; opacity: 1;">Top Rated Teachers</h3>';
                        
                        html += '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
                        html += '<thead><tr style="background: #2c3e50; color: white; font-weight: 600;">';
                        html += '<th style="padding: 14px 10px; text-align: left; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">Teacher Name</th>';
                        html += '<th style="padding: 14px 10px; text-align: center; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">Average Rating</th>';
                        html += '</tr></thead>';
                        html += '<tbody>';
                        data.top_rated.forEach((teacher, index) => {
                            const bgColor = index % 2 === 0 ? '#ffffff' : '#f8f9fa';
                            const rating = teacher.avg_rating ? teacher.avg_rating.toFixed(2) : '0.00';
                            const ratingPercent = (rating / 5) * 100;
                            
                            html += `
                                <tr style="background: ${bgColor}; border-bottom: 1px solid #e8e8e8;">
                                    <td style="padding: 12px 10px; color: #1a1a1a; border-right: 1px solid #e8e8e8;">${teacher.teacher_name || 'N/A'}</td>
                                    <td style="padding: 12px 10px; text-align: center; color: #1a1a1a; border-right: 1px solid #e8e8e8;">
                                        <div style="background: #e8e8e8; height: 20px; border-radius: 3px; overflow: hidden; margin-bottom: 5px;">
                                            <div style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: ${ratingPercent}%; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; font-weight: bold;">
                                                ${ratingPercent > 30 ? rating : ''}
                                            </div>
                                        </div>
                                        <span style="font-weight: 600;">${rating}</span> / 5.00
                                    </td>
                                </tr>
                            `;
                        });
                        html += '</tbody></table>';
                        html += '</div>';
                    }
                    
                    // ===== FOOTER SECTION =====
                    html += `
                        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0; color: #666; font-size: 11px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div>
                                    <p style="margin: 0 0 5px 0; font-weight: 600; color: #1a1a1a;">Fullbright College Inc</p>
                                    <p style="margin: 3px 0; color: #666;">KM 5 National Highway, San Jose, Puerto Princesa, Philippines, 5300</p>
                                    <p style="margin: 3px 0; color: #666;">Email: fullbrightcollege@yahoo.com</p>
                                </div>
                                <div style="text-align: right; color: #999;">
                                    <p style="margin: 0; font-size: 10px;">Teacher Evaluation System</p>
                                    <p style="margin: 3px 0 0 0; font-size: 10px;">Report Generated: ${new Date().toLocaleString()}</p>
                                </div>
                            </div>
                            <div style="border-top: 1px solid #e0e0e0; padding-top: 10px; font-size: 10px; color: #999; text-align: center;">
                                This is an official document from Fullbright College Inc. - Teacher Evaluation & Performance System
                            </div>
                        </div>
                    `;
                    
                    element.innerHTML = html;
                    
                    // Generate PDF
                    const opt = {
                        margin: 12,
                        filename: data.filename,
                        image: { type: 'jpeg', quality: 0.99 },
                        html2canvas: { scale: 2, useCORS: true },
                        jsPDF: { 
                            orientation: 'portrait', 
                            unit: 'mm', 
                            format: 'a4',
                            compress: true
                        }
                    };
                    
                    // Use html2pdf library
                    if (typeof html2pdf !== 'undefined') {
                        html2pdf().set(opt).from(element).save()
                            .then(() => {
                                Swal.close();
                                Swal.fire('Success', 'PDF downloaded successfully!', 'success');
                            })
                            .catch(error => {
                                Swal.close();
                                Swal.fire('Error', 'Failed to generate PDF: ' + error.message, 'error');
                            });
                    } else {
                        // Fallback: show error if library not loaded
                        Swal.close();
                        Swal.fire('Error', 'PDF library failed to load. Please try again.', 'error');
                    }
                }, 500);
            }
        });
        
    } catch (error) {
        console.error('Export Error:', error);
        Swal.fire('Error', 'Failed to export data: ' + error.message, 'error');
    }
}

/**
 * Export Functions for each page
 */

function exportTeachersPDF() {
    exportTableToPDF('teachers', 'Teachers Directory');
}

function exportUsersPDF() {
    exportTableToPDF('users', 'Admin Users');
}

function exportQuestionsPDF() {
    exportTableToPDF('questions', 'Evaluation Questions');
}

function exportResultsPDF() {
    exportTableToPDF('results', 'Evaluation Results');
}

function exportAnalyticsPDF() {
    exportTableToPDF('analytics', 'Analytics Report');
}

function exportDashboardReport() {
    exportTableToPDF('dashboard', 'System Dashboard Report');
}

// Initialize PDF libraries on page load
document.addEventListener('DOMContentLoaded', function() {
    // Preload html2pdf library
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
    script.async = true;
    document.head.appendChild(script);
});
