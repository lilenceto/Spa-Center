# 🏥 Spa Center Admin Panel

## Overview
The Spa Center Admin Panel is a comprehensive management system that allows administrators to manage reservations, approve/reject booking requests, and monitor system activity.

## 🚀 Features

### 1. **Admin Dashboard** (`admin_dashboard.php`)
- **Quick Statistics**: View counts of awaiting, approved, completed, and cancelled reservations
- **Today's Overview**: See total reservations for today and tomorrow
- **Quick Actions**: Direct links to key management functions
- **Recent Activity**: Monitor latest reservation activities
- **System Status**: Check system health and connectivity

### 2. **Admin Panel** (`admin_panel.php`)
- **Reservation Management**: View, approve, reject, and delete reservations
- **Advanced Filtering**: Filter by status, date, and search terms
- **Bulk Actions**: Process multiple reservations at once
- **Real-time Updates**: Instant status changes with AJAX
- **Comprehensive Data**: View client details, service information, and booking times

### 3. **Enhanced Reservation System**
- **Status Management**: Awaiting → Approved → Completed workflow
- **Client Cancellation**: Clients can cancel their own reservations
- **Admin Override**: Admins can manage any reservation
- **Audit Trail**: Track all status changes

## 🔐 Access Control

### Admin Access Required
- Only users with `role = 'admin'` can access the admin panel
- Automatic redirect to login for unauthorized users
- Session-based authentication

### Navigation
- Admin Panel link appears in header navigation for admin users
- Direct access via `/admin_panel.php` and `/admin_dashboard.php`

## 📊 How to Use

### 1. **Accessing the Admin Panel**
```
URL: http://localhost/Spa-Center/admin_panel.php
Login: Use admin credentials
```

### 2. **Managing Reservations**

#### **Individual Actions**
- **Approve**: Click ✅ button to approve awaiting reservations
- **Reject**: Click ❌ button to reject awaiting reservations  
- **Edit**: Click ✏️ button to modify reservation details
- **Delete**: Click 🗑️ button to permanently remove reservations

#### **Bulk Actions**
1. Select multiple reservations using checkboxes
2. Choose action from dropdown:
   - Approve Selected
   - Reject Selected
   - Delete Selected
3. Click "Apply" to process all selected

### 3. **Filtering and Search**

#### **Status Filter**
- All Statuses
- Awaiting (requires approval)
- Approved (confirmed)
- Completed (past appointments)
- Cancelled (rejected/cancelled)

#### **Date Filter**
- All Dates
- Today
- Tomorrow
- This Week
- Past

#### **Search**
- Search by client name, email, service name, or category
- Real-time filtering as you type

### 4. **Statistics Dashboard**

#### **Key Metrics**
- **Awaiting Approval**: Reservations requiring admin action
- **Approved**: Confirmed and ready for service
- **Today's Total**: All reservations scheduled for today
- **Tomorrow's Total**: Upcoming reservations

#### **Color Coding**
- 🟡 **Awaiting**: Requires attention
- 🟢 **Approved**: Confirmed
- 🔵 **Completed**: Past appointments
- 🔴 **Cancelled**: Rejected/cancelled

## 🛠️ Technical Details

### **Files Created/Modified**
- `admin_panel.php` - Main reservation management interface
- `admin_dashboard.php` - Overview dashboard
- `reservation_status.php` - Enhanced with reject functionality
- `header.php` - Added admin navigation link

### **Database Operations**
- **Status Updates**: `UPDATE reservations SET status = ? WHERE id = ?`
- **Bulk Processing**: Loop through selected IDs for batch operations
- **Transaction Safety**: Uses database transactions for data integrity

### **Security Features**
- **Role Verification**: Checks user role before allowing access
- **Input Validation**: Sanitizes all user inputs
- **SQL Injection Protection**: Uses prepared statements
- **Session Management**: Secure session handling

## 📱 Responsive Design

### **Mobile Optimized**
- Responsive grid layouts
- Touch-friendly buttons
- Optimized table views for small screens
- Collapsible navigation

### **Desktop Features**
- Full-width tables
- Hover effects
- Advanced filtering options
- Bulk action capabilities

## 🔄 Workflow Examples

### **Scenario 1: Approving a Reservation**
1. Client makes reservation → Status: "Awaiting"
2. Admin reviews in admin panel
3. Admin clicks ✅ Approve button
4. Status changes to "Approved"
5. Client receives confirmation

### **Scenario 2: Rejecting a Reservation**
1. Admin reviews reservation request
2. Admin clicks ❌ Reject button
3. Status changes to "Cancelled"
4. Client can see updated status
5. Option to make new reservation

### **Scenario 3: Bulk Processing**
1. Admin selects multiple awaiting reservations
2. Chooses "Approve Selected" from bulk actions
3. Confirms action
4. All selected reservations are approved at once
5. Success message shows count of processed items

## 🚨 Troubleshooting

### **Common Issues**

#### **"Access Denied" Error**
- Ensure user is logged in
- Verify user role is 'admin'
- Check session variables

#### **Reservations Not Showing**
- Check database connection
- Verify reservation data exists
- Review filter settings

#### **Bulk Actions Not Working**
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify reservation selection

### **Debug Tools**
- Use `debug_cancel.php` to test cancellation functionality
- Check browser console for JavaScript errors
- Review server error logs

## 🔮 Future Enhancements

### **Planned Features**
- Email notifications for status changes
- Calendar view of reservations
- Employee assignment management
- Reporting and analytics
- Export functionality (CSV/PDF)

### **Integration Possibilities**
- SMS notifications
- Payment processing
- Customer feedback system
- Inventory management

## 📞 Support

### **Getting Help**
1. Check this README first
2. Review browser console for errors
3. Check server error logs
4. Verify database connectivity

### **Testing**
- Use `test_cancel_button.php` for testing
- Create test reservations as different users
- Test all admin roles and permissions

---

**🎉 Your Spa Center Admin Panel is now ready to use!**

Start by visiting `/admin_dashboard.php` for an overview, then use `/admin_panel.php` for detailed reservation management.
