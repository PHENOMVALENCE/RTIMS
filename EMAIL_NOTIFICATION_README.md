# RTIMS Email Notification System

## Overview
The RTIMS (Road Traffic Incidents Management System) now includes an automated email notification system that sends alerts to designated administrators when new traffic incidents are recorded.

## Features

### 📧 Automated Email Notifications
- **Trigger**: Automatically sends email when a traffic officer records a new incident
- **Recipient**: Configurable email address (default: mwiganivalence@gmail.com)
- **Format**: Professional HTML email with complete incident details

### 🎨 Professional Email Design
- **Modern Styling**: Clean, responsive HTML email template
- **Comprehensive Details**: Includes all incident information
- **Visual Elements**: Icons, color coding, and structured layout
- **Mobile Friendly**: Responsive design for all devices

### ⚙️ Configuration Management
- **Admin Panel**: Dedicated email configuration page (`/admin/email_config.php`)
- **Toggle Control**: Enable/disable notifications
- **Email Settings**: Configure sender, recipient, and reply-to addresses
- **Real-time Testing**: Built-in test functionality

## Email Content

Each notification email includes:

### 📋 Incident Information
- **Control Number**: Unique incident identifier
- **Driver Details**: Name and license number
- **Officer Information**: Recording officer and badge number
- **Offence Details**: Type and description
- **Location**: Detailed incident location
- **Timestamp**: Date and time of recording
- **Evidence Status**: Whether photos were uploaded

### 🎯 Professional Formatting
- **Header**: RTIMS branding and system identification
- **Alert Section**: Clear incident notification
- **Details Grid**: Organized information display
- **Call-to-Action**: Link to view full incident details
- **Footer**: System information and branding

## Technical Implementation

### 📁 File Structure
```
/config/
  ├── database.php          # Main database and utility functions
  ├── email_config.php      # Email configuration constants
  
/admin/
  ├── email_config.php      # Email settings management page
  
/officer/
  ├── record_incident.php   # Modified to send notifications
  
/test_email_notification.php # Email testing utility
```

### 🔧 Configuration Files

#### `/config/email_config.php`
```php
define('EMAIL_NOTIFICATIONS_ENABLED', true);
define('NOTIFICATION_EMAIL', 'mwiganivalence@gmail.com');
define('SYSTEM_EMAIL_FROM', 'RTIMS Notifications <noreply@rtims.gov.tz>');
define('SYSTEM_EMAIL_REPLY_TO', 'admin@rtims.gov.tz');
```

### 📨 Email Functions

#### `sendIncidentNotification($incident_data)`
- **Purpose**: Sends HTML email notification
- **Parameters**: Array with incident details
- **Returns**: Boolean success status
- **Features**: Error logging, priority headers

#### `createIncidentEmailHTML($incident_data)`
- **Purpose**: Generates HTML email template
- **Parameters**: Array with incident details
- **Returns**: Complete HTML email content
- **Features**: Responsive design, professional styling

## Usage

### 🚀 For Administrators
1. **Access Configuration**: Go to Admin Dashboard → Email Settings
2. **Enable Notifications**: Check "Enable Email Notifications"
3. **Set Email Address**: Enter notification recipient email
4. **Configure Sender**: Set appropriate from/reply-to addresses
5. **Test System**: Use "Test Email" button to verify setup

### 👮 For Officers
- **Automatic**: No additional steps required
- **Recording Process**: Email sent automatically after successful incident recording
- **Confirmation**: Success message indicates if email was sent
- **Error Handling**: Email failures don't affect incident recording

### 🧪 Testing
- **Test Page**: Visit `/test_email_notification.php`
- **Sample Data**: Generates test incident with sample information
- **Preview**: Shows email content and sending status
- **Debugging**: Displays any error messages

## Server Requirements

### 📧 PHP Mail Function
- **Required**: PHP `mail()` function must be available
- **SMTP**: Server should have outbound SMTP configured
- **Authentication**: May require authenticated SMTP for production

### 🔒 Security Considerations
- **Valid Domains**: Use legitimate email domains to avoid spam filters
- **SPF Records**: Configure DNS SPF records for email domain
- **Rate Limiting**: Consider email rate limits for high-volume systems

## Troubleshooting

### ❌ Common Issues

#### Email Not Sending
1. **Check Server**: Verify PHP `mail()` function works
2. **SMTP Configuration**: Ensure server can send outbound emails
3. **Error Logs**: Check PHP error logs for mail-related errors
4. **Test Page**: Use test utility to diagnose issues

#### Emails in Spam
1. **Valid Sender**: Use legitimate domain for sender address
2. **SPF Records**: Configure DNS properly
3. **Content**: Avoid spam trigger words in subject/content

#### Configuration Issues
1. **File Permissions**: Ensure config files are writable
2. **Path Issues**: Verify all file paths are correct
3. **Syntax Errors**: Check PHP syntax in configuration files

### 🔍 Debugging
- **Error Logs**: Check `/error_log` files
- **Test Utility**: Use built-in test page
- **Manual Testing**: Test with simple PHP mail script

## Customization

### 🎨 Email Template
- **File**: `createIncidentEmailHTML()` function in `/config/database.php`
- **Styling**: Modify CSS within the HTML template
- **Content**: Add/remove sections as needed
- **Branding**: Update logos, colors, and text

### ⚙️ Configuration Options
- **Additional Recipients**: Modify to support multiple email addresses
- **Email Types**: Add different templates for different incident types
- **Scheduling**: Implement digest emails or scheduled reporting

## Integration Notes

### 🔗 Existing System
- **Non-intrusive**: Doesn't affect existing functionality
- **Error Handling**: Email failures don't break incident recording
- **Optional**: Can be completely disabled if not needed

### 📈 Future Enhancements
- **SMS Notifications**: Add text message alerts
- **Multiple Recipients**: Support for multiple notification emails
- **Template System**: Multiple email templates for different scenarios
- **Dashboard Integration**: Email statistics and monitoring

## Support

For technical support or customization requests, please refer to the system administrator or development team.

---

**RTIMS Email Notification System**  
*Automated incident reporting for efficient traffic management*
