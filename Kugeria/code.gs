/**
 * ═══════════════════════════════════════════════════════════════
 *  KŪGERIA LTD — Google Apps Script
 *  Connects schedule.html form → Google Sheet + Email notification
 *
 *  SETUP INSTRUCTIONS:
 *  ─────────────────────────────────────────────────────────────
 *  1. Go to https://script.google.com → New Project
 *  2. Paste this entire file into the editor
 *  3. Replace SHEET_ID and NOTIFY_EMAIL below with your values
 *  4. Click Deploy → New Deployment → Web App
 *     - Execute as: Me
 *     - Who has access: Anyone
 *  5. Copy the Web App URL
 *  6. Paste it into schedule.html where it says SCRIPT_URL
 *  7. Re-deploy after any code change (Deploy → Manage Deployments → Edit)
 *  8. To generate the analytics dashboard, run createDashboard() manually
 *     from the Apps Script editor, or set a time-based trigger for it.
 * ═══════════════════════════════════════════════════════════════
 */

// ── CONFIG — UPDATE THESE ──────────────────────────────────────
const SHEET_ID     = 'YOUR_GOOGLE_SHEET_ID_HERE';   // From the Sheet URL: /d/SHEET_ID/edit
const NOTIFY_EMAIL = 'hello@kugeria.co.ke';          // Where to receive booking alerts
const SHEET_TAB    = 'Bookings';                     // Sheet tab name
const DASH_TAB     = 'Dashboard';                   // Analytics dashboard tab name
const BRAND_NAME   = 'Kūgeria Ltd';
const BRAND_COLOR  = '#052F5F';
const GOLD         = '#C5A059';
const RATE_LIMIT   = 5;                              // Max submissions per email within 24 hours
// ──────────────────────────────────────────────────────────────


/**
 * Handles POST requests from the booking form.
 * Entry point — Apps Script calls this when the form submits via fetch().
 */
function doPost(e) {
  try {
    // Parse incoming JSON from the form
    const raw  = e.postData ? e.postData.contents : '{}';
    const data = JSON.parse(raw);

    // Block bot submissions caught by the honeypot field
    if (data.honeypot && data.honeypot.trim() !== '') {
      return jsonResponse({ status: 'blocked', message: 'Bot submission detected.' });
    }

    // Reject if any required fields are missing
    const required = ['fullName', 'contact', 'email', 'services', 'vision', 'proposedDate', 'meetingType'];
    for (const field of required) {
      if (!data[field] || String(data[field]).trim() === '') {
        return jsonResponse({ status: 'error', message: 'Missing required field: ' + field });
      }
    }

    // Basic email format validation
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
      return jsonResponse({ status: 'error', message: 'Invalid email address.' });
    }

    // Rate-limit: reject if this email has submitted 5+ times in the past 24 hours
    if (isRateLimited(data.email)) {
      return jsonResponse({ status: 'error', message: 'Too many submissions. Please try again later.' });
    }

    // Write data to the Bookings sheet
    writeToSheet(data);

    // Send alert email to Isaac
    sendNotificationEmail(data);

    // Send confirmation email to the client
    sendConfirmationEmail(data);

    return jsonResponse({ status: 'success', message: 'Booking received.' });

  } catch (err) {
    Logger.log('doPost error: ' + err.message);
    return jsonResponse({ status: 'error', message: err.message });
  }
}


/**
 * Handles GET requests — used to verify the script is live.
 */
function doGet(e) {
  return jsonResponse({ status: 'ok', message: BRAND_NAME + ' booking script is live.' });
}


// ══════════════════════════════════════════════════════════════
//  RATE LIMITER
// ══════════════════════════════════════════════════════════════
/**
 * Returns true if the given email has already submitted RATE_LIMIT or more
 * times in the past 24 hours. Reads Timestamp (col 1) and Email (col 3) only.
 * Fails open on any error — a read failure won't block legitimate users.
 */
function isRateLimited(email) {
  try {
    const ss    = SpreadsheetApp.openById(SHEET_ID);
    const sheet = ss.getSheetByName(SHEET_TAB);
    if (!sheet || sheet.getLastRow() < 2) return false;

    const data       = sheet.getRange(2, 1, sheet.getLastRow() - 1, 3).getValues();
    const cutoff     = new Date(Date.now() - 24 * 60 * 60 * 1000);
    const lowerEmail = email.toLowerCase().trim();
    let count = 0;

    for (const row of data) {
      const rowDate  = new Date(row[0]);
      const rowEmail = String(row[2]).toLowerCase().trim();
      if (rowEmail === lowerEmail && rowDate >= cutoff) count++;
    }

    return count >= RATE_LIMIT;
  } catch (err) {
    Logger.log('isRateLimited error: ' + err.message);
    return false;
  }
}


// ══════════════════════════════════════════════════════════════
//  SHEET WRITER
// ══════════════════════════════════════════════════════════════
/**
 * Appends a new booking row to the Bookings sheet.
 * Auto-creates the tab with a styled header row on first run.
 */
function writeToSheet(data) {
  const ss    = SpreadsheetApp.openById(SHEET_ID);
  let   sheet = ss.getSheetByName(SHEET_TAB);

  // Create the tab + styled header on first run
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_TAB);
    const headers = [
      'Timestamp', 'Full Name', 'Email', 'Contact / WhatsApp',
      'Company', 'Services Interested In', 'Project Vision',
      'Budget Range', 'Proposed Date', 'Preferred Time',
      'Meeting Type', 'Referral Source', 'Status'
    ];
    sheet.appendRow(headers);
    const hdr = sheet.getRange(1, 1, 1, headers.length);
    hdr.setBackground(BRAND_COLOR)
       .setFontColor('#FFFFFF')
       .setFontWeight('bold')
       .setFontSize(11);
    sheet.setFrozenRows(1);
  }

  // Parse the client-sent timestamp safely; fall back to server time if malformed
  let ts;
  try {
    const parsed = data.timestamp ? new Date(data.timestamp) : null;
    ts = (parsed && !isNaN(parsed.getTime()))
      ? parsed.toLocaleString('en-KE', { timeZone: 'Africa/Nairobi' })
      : new Date().toLocaleString('en-KE', { timeZone: 'Africa/Nairobi' });
  } catch (e) {
    ts = new Date().toLocaleString('en-KE', { timeZone: 'Africa/Nairobi' });
  }

  // Append the booking row
  sheet.appendRow([
    ts,
    data.fullName      || '—',
    data.email         || '—',
    data.contact       || '—',
    data.company       || '—',
    data.services      || '—',
    data.vision        || '—',
    data.budget        || '—',
    data.proposedDate  || '—',
    data.preferredTime || 'Flexible',
    data.meetingType   || '—',
    data.referral      || '—',
    'New ✦'
  ]);

  sheet.autoResizeColumns(1, 13);

  // Alternate row shading for readability
  const lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    sheet.getRange(lastRow, 1, 1, 13)
         .setBackground(lastRow % 2 === 0 ? '#f9fafb' : '#ffffff');
  }
}


// ══════════════════════════════════════════════════════════════
//  NOTIFICATION EMAIL → ISAAC
// ══════════════════════════════════════════════════════════════
/**
 * Sends an HTML alert email to NOTIFY_EMAIL with full booking details
 * and a one-click reply CTA.
 */
function sendNotificationEmail(data) {
  const subject = '📅 New Booking — ' + data.fullName + ' (' + data.meetingType + ') | ' + BRAND_NAME;

  const body = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }
    .wrap { max-width: 580px; margin: 0 auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
    .header { background: ${BRAND_COLOR}; padding: 28px 30px; }
    .header h1 { color: #fff; font-size: 22px; margin: 14px 0 4px; font-weight: 700; }
    .header p { color: rgba(255,255,255,.7); font-size: 13px; margin: 0; }
    .body { padding: 28px 30px; }
    .alert { background: rgba(197,160,89,.1); border: 1.5px solid ${GOLD}; border-radius: 9px; padding: 14px 18px; margin-bottom: 22px; font-size: 13.5px; color: #7a5e1e; font-weight: 600; }
    .section { margin-bottom: 22px; }
    .section-title { font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: ${GOLD}; margin-bottom: 10px; }
    .field { margin-bottom: 10px; }
    .label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 2px; }
    .value { font-size: 14px; color: #212121; line-height: 1.6; }
    .vision-box { background: #f9fafb; border-radius: 8px; padding: 14px; font-size: 13.5px; color: #374151; line-height: 1.7; border-left: 3px solid ${GOLD}; }
    .badge { display: inline-block; padding: 5px 12px; border-radius: 100px; font-size: 12px; font-weight: 600; background: rgba(5,47,95,.08); color: ${BRAND_COLOR}; }
    .cta-btn { display: block; background: ${GOLD}; color: #212121; text-align: center; padding: 13px 24px; border-radius: 9px; font-size: 14px; font-weight: 700; text-decoration: none; margin-top: 24px; }
    .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 18px 30px; font-size: 11.5px; color: #9ca3af; text-align: center; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>📅 New Meeting Booking</h1>
    <p>A new session has been requested on the Kūgeria Ltd portfolio site</p>
  </div>
  <div class="body">
    <div class="alert">✦ Action Required — Confirm this booking within 24 hours</div>

    <div class="section">
      <div class="section-title">Client Details</div>
      <div class="field"><div class="label">Full Name</div><div class="value">${escHtml(data.fullName)}</div></div>
      <div class="field"><div class="label">Email</div><div class="value"><a href="mailto:${escHtml(data.email)}">${escHtml(data.email)}</a></div></div>
      <div class="field"><div class="label">WhatsApp / Phone</div><div class="value"><a href="https://wa.me/${escHtml(data.contact.replace(/\D/g,''))}">${escHtml(data.contact)}</a></div></div>
      <div class="field"><div class="label">Company</div><div class="value">${escHtml(data.company || '—')}</div></div>
    </div>

    <div class="section">
      <div class="section-title">Project Interest</div>
      <div class="field"><div class="label">Services</div><div class="value"><span class="badge">${escHtml(data.services)}</span></div></div>
      <div class="field"><div class="label">Budget Range</div><div class="value">${escHtml(data.budget || 'Not specified')}</div></div>
      <div class="field"><div class="label">Project Vision</div><div class="vision-box">${escHtml(data.vision)}</div></div>
    </div>

    <div class="section">
      <div class="section-title">Schedule</div>
      <div class="field"><div class="label">Proposed Date</div><div class="value">${escHtml(data.proposedDate)}</div></div>
      <div class="field"><div class="label">Preferred Time</div><div class="value">${escHtml(data.preferredTime || 'Flexible')}</div></div>
      <div class="field"><div class="label">Meeting Type</div><div class="value"><span class="badge">${escHtml(data.meetingType)}</span></div></div>
      <div class="field"><div class="label">Referral Source</div><div class="value">${escHtml(data.referral || '—')}</div></div>
    </div>

    <a href="mailto:${escHtml(data.email)}?subject=Re: Your Kūgeria Ltd Meeting Request&body=Hi ${escHtml(data.fullName.split(' ')[0])},%0D%0A%0D%0AThank you for reaching out! I'd love to confirm your session...%0D%0A%0D%0ABest,%0D%0AIsaac Oonge%0D%0AKūgeria Ltd" class="cta-btn">
      ✉️  Reply to ${escHtml(data.fullName)} →
    </a>
  </div>
  <div class="footer">
    This notification was auto-generated by the Kūgeria Ltd booking system.<br>
    © 2026 Kūgeria Ltd · Nairobi, Kenya
  </div>
</div>
</body>
</html>
  `;

  GmailApp.sendEmail(NOTIFY_EMAIL, subject, '', { htmlBody: body });
}


// ══════════════════════════════════════════════════════════════
//  CONFIRMATION EMAIL → CLIENT
// ══════════════════════════════════════════════════════════════
/**
 * Sends an HTML confirmation email to the client with a booking summary
 * and three numbered next-steps.
 */
function sendConfirmationEmail(data) {
  const subject = 'Your meeting request is received — ' + BRAND_NAME;

  const body = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }
    .wrap { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
    .header { background: linear-gradient(135deg, ${BRAND_COLOR}, #004B23); padding: 36px 30px; text-align: center; }
    .checkmark { font-size: 44px; display: block; margin-bottom: 12px; }
    .header h1 { color: #fff; font-size: 24px; margin: 0 0 6px; }
    .header p { color: rgba(255,255,255,.7); font-size: 13px; margin: 0; }
    .body { padding: 30px; }
    .greeting { font-size: 16px; color: #212121; margin-bottom: 14px; line-height: 1.65; }
    .summary-box { background: #f9fafb; border-radius: 10px; padding: 18px; border: 1px solid #e5e7eb; margin: 20px 0; }
    .summary-title { font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: ${GOLD}; margin-bottom: 12px; }
    .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
    .row .lbl { color: #9ca3af; font-weight: 500; }
    .row .val { color: #212121; font-weight: 600; text-align: right; max-width: 60%; }
    .next-steps { margin: 22px 0; }
    .step { display: flex; gap: 13px; margin-bottom: 14px; align-items: flex-start; }
    .step-num { width: 28px; height: 28px; border-radius: 50%; background: ${GOLD}; color: #212121; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; margin-top: 2px; }
    .step-text { font-size: 13.5px; color: #374151; line-height: 1.6; }
    .step-text strong { color: #212121; }
    .cta-btn { display: block; background: ${GOLD}; color: #212121; text-align: center; padding: 13px 24px; border-radius: 9px; font-size: 14px; font-weight: 700; text-decoration: none; margin-top: 24px; }
    .footer { background: #212121; padding: 20px 30px; font-size: 11.5px; color: rgba(255,255,255,.4); text-align: center; }
    .footer a { color: ${GOLD}; text-decoration: none; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <span class="checkmark">🎉</span>
    <h1>You're all set, ${escHtml(data.fullName.split(' ')[0])}!</h1>
    <p>Your meeting request has been received by Kūgeria Ltd</p>
  </div>
  <div class="body">
    <p class="greeting">
      Thank you for reaching out! Isaac will review your request and get back to you within <strong>24 hours</strong> to confirm the details and send a meeting link or location.
    </p>

    <div class="summary-box">
      <div class="summary-title">Your Booking Summary</div>
      <div class="row"><span class="lbl">Services</span><span class="val">${escHtml(data.services)}</span></div>
      <div class="row"><span class="lbl">Proposed Date</span><span class="val">${escHtml(data.proposedDate)}</span></div>
      <div class="row"><span class="lbl">Preferred Time</span><span class="val">${escHtml(data.preferredTime || 'Flexible')}</span></div>
      <div class="row"><span class="lbl">Meeting Type</span><span class="val">${escHtml(data.meetingType)}</span></div>
    </div>

    <div class="next-steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-text"><strong>Check your inbox</strong> — Isaac will send a confirmation email within 24 hours.</div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-text"><strong>Prepare your ideas</strong> — jot down any features, references, or questions you'd like to discuss.</div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-text"><strong>Show up ready</strong> — the call is free and relaxed. We'll map your vision and outline a clear path forward.</div>
      </div>
    </div>

    <p style="font-size:13px;color:#6b7280;margin-top:4px;">
      Can't wait? Reach out directly on <a href="https://instagram.com/kugeria" style="color:${BRAND_COLOR};font-weight:600">Instagram @kugeria</a> or reply to this email.
    </p>

    <a href="https://kugeria.co.ke" class="cta-btn">✦ Visit Kūgeria Portfolio →</a>
  </div>
  <div class="footer">
    © 2026 Kūgeria Ltd · Nairobi, Kenya<br>
    <a href="https://kugeria.co.ke">kugeria.co.ke</a> ·
    <a href="mailto:hello@kugeria.co.ke">hello@kugeria.co.ke</a>
  </div>
</div>
</body>
</html>
  `;

  GmailApp.sendEmail(data.email, subject, '', { htmlBody: body, replyTo: NOTIFY_EMAIL });
}


// ══════════════════════════════════════════════════════════════
//  ANALYTICS DASHBOARD
// ══════════════════════════════════════════════════════════════
/**
 * Builds (or fully refreshes) a "Dashboard" sheet with analytics
 * derived from all rows in the Bookings sheet.
 *
 * HOW TO RUN:
 *  - Manually: Apps Script editor → select createDashboard → click Run
 *  - Automated: Triggers → Add Trigger → Function: createDashboard
 *    → Event source: Time-driven → e.g. Daily timer at 6am
 *
 * SECTIONS PRODUCED:
 *  1. Summary KPIs       — total, new, confirmed bookings
 *  2. Service Demand     — count per service type (multi-select aware)
 *  3. Referral Sources   — which channels drive the most leads
 *  4. Budget Distribution— how budget ranges break down
 *  5. Meeting Type Prefs — Online vs Physical vs Phone Call split
 *  6. Monthly Volume     — bookings per calendar month (chronological)
 *  7. Popular Time Slots — which time windows are most requested
 */
function createDashboard() {
  const ss       = SpreadsheetApp.openById(SHEET_ID);
  const bookings = ss.getSheetByName(SHEET_TAB);

  if (!bookings || bookings.getLastRow() < 2) {
    Logger.log('No booking data found. Dashboard not created.');
    return;
  }

  // Read all data rows (skip the header)
  const numRows = bookings.getLastRow() - 1;
  const raw     = bookings.getRange(2, 1, numRows, 13).getValues();

  // Column indices (0-based):
  // 0=Timestamp  1=Name  2=Email  3=Contact  4=Company
  // 5=Services   6=Vision  7=Budget  8=Date  9=Time
  // 10=MeetType  11=Referral  12=Status
  const COL = { ts: 0, services: 5, budget: 7, time: 9, meetType: 10, referral: 11, status: 12 };

  // Aggregate counts
  const serviceCount  = {};
  const referralCount = {};
  const budgetCount   = {};
  const meetCount     = {};
  const monthCount    = {};
  const timeCount     = {};

  for (const row of raw) {
    // Services may be comma-separated (multi-select)
    const svcs = String(row[COL.services] || '').split(',').map(s => s.trim()).filter(Boolean);
    svcs.forEach(s => { serviceCount[s] = (serviceCount[s] || 0) + 1; });

    const ref = String(row[COL.referral] || '—').trim();
    referralCount[ref] = (referralCount[ref] || 0) + 1;

    const bud = String(row[COL.budget] || '—').trim();
    budgetCount[bud] = (budgetCount[bud] || 0) + 1;

    const mt = String(row[COL.meetType] || '—').trim();
    meetCount[mt] = (meetCount[mt] || 0) + 1;

    try {
      const d = new Date(row[COL.ts]);
      if (!isNaN(d.getTime())) {
        const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        monthCount[key] = (monthCount[key] || 0) + 1;
      }
    } catch (e) {}

    const ts = String(row[COL.time] || 'Flexible').trim();
    timeCount[ts] = (timeCount[ts] || 0) + 1;
  }

  // Status totals
  const confirmedCount = raw.filter(r => String(r[COL.status]).toLowerCase().includes('confirmed')).length;
  const newCount       = raw.filter(r => String(r[COL.status]).toLowerCase().includes('new')).length;

  // Clear or create the Dashboard tab
  let dash = ss.getSheetByName(DASH_TAB);
  if (dash) {
    dash.clearContents();
    dash.clearFormats();
  } else {
    dash = ss.insertSheet(DASH_TAB);
    ss.setActiveSheet(dash);
    ss.moveActiveSheet(2);
  }

  let row = 1;

  function writeHeader(title) {
    dash.getRange(row, 1, 1, 2)
        .merge()
        .setValue(title)
        .setBackground(BRAND_COLOR)
        .setFontColor('#FFFFFF')
        .setFontWeight('bold')
        .setFontSize(11);
    row++;
  }

  function writeRow(label, value, isSubHeader) {
    dash.getRange(row, 1).setValue(label);
    dash.getRange(row, 2).setValue(value);
    if (isSubHeader) {
      dash.getRange(row, 1, 1, 2).setBackground('#f3f4f6').setFontWeight('bold');
    }
    row++;
  }

  function writeSortedTable(countObj) {
    Object.entries(countObj)
      .sort((a, b) => b[1] - a[1])
      .forEach(([label, count]) => writeRow(label, count));
  }

  function spacer() { row++; }

  // 1. Summary KPIs
  writeHeader('SUMMARY');
  writeRow('Total Bookings',    numRows);
  writeRow('New (Unreviewed)',  newCount);
  writeRow('Confirmed',         confirmedCount);
  writeRow('Other Statuses',    numRows - newCount - confirmedCount);
  spacer();

  // 2. Service Demand
  writeHeader('SERVICE DEMAND');
  writeRow('Service', 'Count', true);
  writeSortedTable(serviceCount);
  spacer();

  // 3. Referral Sources
  writeHeader('REFERRAL SOURCES');
  writeRow('Source', 'Count', true);
  writeSortedTable(referralCount);
  spacer();

  // 4. Budget Distribution
  writeHeader('BUDGET DISTRIBUTION');
  writeRow('Budget Range', 'Count', true);
  writeSortedTable(budgetCount);
  spacer();

  // 5. Meeting Type Preferences
  writeHeader('MEETING TYPE PREFERENCES');
  writeRow('Type', 'Count', true);
  writeSortedTable(meetCount);
  spacer();

  // 6. Monthly Volume (chronological order)
  writeHeader('MONTHLY VOLUME');
  writeRow('Month (YYYY-MM)', 'Bookings', true);
  Object.entries(monthCount)
    .sort((a, b) => a[0].localeCompare(b[0]))
    .forEach(([month, count]) => writeRow(month, count));
  spacer();

  // 7. Popular Time Slots
  writeHeader('PREFERRED TIME SLOTS');
  writeRow('Time Slot', 'Count', true);
  writeSortedTable(timeCount);

  // Final formatting
  dash.setColumnWidth(1, 240);
  dash.setColumnWidth(2, 100);
  const lastUsedRow = row - 1;
  for (let r = 1; r <= lastUsedRow; r++) {
    const val = dash.getRange(r, 2).getValue();
    if (typeof val === 'number') {
      dash.getRange(r, 2)
          .setHorizontalAlignment('center')
          .setFontWeight('bold')
          .setFontColor(BRAND_COLOR);
    }
  }

  Logger.log('Dashboard created/updated. ' + numRows + ' bookings processed.');
}


// ══════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════

/** Returns a JSON ContentService response */
function jsonResponse(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

/**
 * Escapes HTML special characters to prevent XSS in email bodies.
 * Covers the full OWASP-recommended set: & < > " ' /
 */
function escHtml(str) {
  if (!str) return '—';
  return String(str)
    .replace(/&/g,  '&amp;')
    .replace(/</g,  '&lt;')
    .replace(/>/g,  '&gt;')
    .replace(/"/g,  '&quot;')
    .replace(/'/g,  '&#39;')
    .replace(/\//g, '&#x2F;');
}
