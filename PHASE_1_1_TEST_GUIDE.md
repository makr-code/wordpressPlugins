# Phase 1.1 - Integration Test Guide

**Purpose:** Verify that automatic support benefits work correctly through the complete order → license → support flow.

---

## Prerequisites

1. ThemisDB Order Request plugin activated
2. ThemisDB Support Portal plugin activated  
3. WordPress admin access
4. Test database with clean state (recommended)

---

## Test Scenario 1: Order Processing → Support Benefits Auto-Creation

**Steps:**

1. **Create Test Order (Frontend)**
   - Navigate to `/themisdb-order/` (or wherever order form is)
   - Fill out order form:
     - Product Edition: `enterprise`
     - All required customer information
   - Submit order
   - Verify order created (check `wp_themisdb_orders` table)

2. **Check License Creation**
   - Go to admin panel → Orders section
   - Find your test order
   - Verify license was created (`wp_themisdb_licenses.id`)
   
3. **Verify Support Benefits Created**
   - Open phpMyAdmin or command line
   - Query: `SELECT * FROM wp_themisdb_support_benefits WHERE license_id = {license_id}`
   - **Expected Result:**
     - 1 row exists
     - `tier_level = 'enterprise'`
     - `benefit_status = 'pending'` (not active yet)
     - `max_open_tickets = unlimited` (NULL)
     - `max_tickets_per_month = unlimited` (NULL)
     - `response_sla_hours = 8`
     - `priority_can_assign = 1`
     - `included_hours_per_month = 40`

**Success Criteria:**
- ✅ Benefit auto-created with correct tier
- ✅ All fields populated according to tier

---

## Test Scenario 2: License Activation → Support Benefits Activation

**Steps:**

1. **Activate License from Admin Panel**
   - Go to admin panel → Licenses section
   - Find your test license
   - Click "Activate" button
   - Verify license status changed to `active`

2. **Verify Support Benefits Activated**
   - Query: `SELECT benefit_status, activated_at FROM wp_themisdb_support_benefits WHERE license_id = {license_id}`
   - **Expected Result:**
     - `benefit_status = 'active'`
     - `activated_at = current datetime`

**Success Criteria:**
- ✅ Benefit status transitioned to active
- ✅ Activation timestamp recorded

---

## Test Scenario 3: Ticket Creation with Support Benefit Validation

**Steps:**

1. **Create Support Ticket via Frontend**
   - Go to support portal
   - Click "Create Ticket"
   - Fill out form:
     - Subject: "Test ticket"
     - Message: "This is a test"
     - License Key: (use your test license key)
     - Priority: "high"
   - Submit

2. **Verify Ticket Created**
   - Check `wp_themisdb_support_tickets` table has new row
   - Check license_key matches your test license
   
3. **Verify Usage Counter Incremented**
   - Query: `SELECT tickets_used_this_month FROM wp_themisdb_support_benefits WHERE license_id = {license_id}`
   - **Expected Result:**
     - `tickets_used_this_month = 1`

**Success Criteria:**
- ✅ Ticket created successfully
- ✅ Limits check passed (enterprise has unlimited quota)
- ✅ Usage counter incremented

---

## Test Scenario 4: Community Tier Limit Enforcement

**Steps:**

1. **Create Test Order for Community**
   - Create new order with `product_edition = 'community'`
   - Activate the generated license

2. **Verify Community Benefits Created**
   - Query benefits table for this license
   - **Verify fields:**
     - `tier_level = 'community'`
     - `max_open_tickets = 5`
     - `max_tickets_per_month = 12`
     - `response_sla_hours = 48`
     - `priority_can_assign = 0`

3. **Create 5 Test Tickets**
   - Create 5 tickets with license key
   - All should succeed
   - Check `tickets_used_this_month = 5`

4. **Attempt 6th Ticket (Should Fail)**
   - Try to create 6th ticket
   - **Expected Result:**
     - Ticket creation blocked
     - Error message: "Maximum open tickets exceeded"
     - Ticket NOT created in database

**Success Criteria:**
- ✅ Tier configuration correctly applied
- ✅ Community limit enforced at 5 open tickets
- ✅ Exceeding limit properly blocked

---

## Test Scenario 5: Priority Assignment Validation

**Steps:**

1. **Community Tier - Try High Priority (Should Fail)**
   - Create ticket with `priority = 'high'` on community tier
   - **Expected Result:**
     - Creation blocked
     - Error message: "Priority assignment not allowed for this tier"

2. **Enterprise Tier - Try High Priority (Should Succeed)**
   - Create ticket with `priority = 'high'` on enterprise tier
   - **Expected Result:**
     - Created successfully
     - Ticket stored with `priority = 'high'`

**Success Criteria:**
- ✅ Community tier correctly rejects priority assignments
- ✅ Enterprise tier accepts priority assignments

---

## Test Scenario 6: License Suspension → Support Benefit Suspension

**Steps:**

1. **Suspend Test License**
   - Go to admin panel → Licenses
   - Find test license
   - Click "Suspend" button
   - Enter reason: "Test suspension"

2. **Verify Support Benefits Suspended**
   - Query: `SELECT benefit_status FROM wp_themisdb_support_benefits WHERE license_id = {license_id}`
   - **Expected Result:**
     - `benefit_status = 'suspended'`

3. **Try Creating Ticket (Should Fail)**
   - Try to create ticket with suspended license key
   - **Expected Result:**
     - Creation blocked
     - Error message: "Support benefit status is suspended"

**Success Criteria:**
- ✅ Benefit status transitioned to suspended
- ✅ Ticket creation blocked for suspended benefits

---

## Test Scenario 7: License Cancellation → Support Benefit Deactivation

**Steps:**

1. **Cancel Test License**
   - Go to admin panel → Licenses
   - Find test license
   - Click "Cancel" button
   - Enter reason: "Test cancellation"

2. **Verify Support Benefits Deactivated**
   - Query: `SELECT benefit_status FROM wp_themisdb_support_benefits WHERE license_id = {license_id}`
   - **Expected Result:**
     - `benefit_status = 'expired'` or `'deactivated'`

3. **Try Creating Ticket (Should Fail)**
   - Try to create ticket with cancelled license key
   - **Expected Result:**
     - Creation blocked
     - Error message: "Support benefit status is expired/deactivated"

**Success Criteria:**
- ✅ Benefit status transitioned to inactive
- ✅ Ticket creation permanently blocked

---

## Test Scenario 8: Cron Job Execution (Monthly Reset)

**Steps:**

1. **Manually Trigger Cron Job**
   - From command line or WordPress admin:
     ```bash
     wp cron event run themisdb_support_benefits_monthly_reset --allow-root
     ```

2. **Create Multiple Tickets to Build Usage**
   - Create 5 tickets on your test benefit
   - Check `tickets_used_this_month = 5`

3. **Trigger Monthly Reset**
   - Run cron: `wp cron event run themisdb_support_benefits_monthly_reset`

4. **Verify Reset Executed**
   - Query: `SELECT tickets_used_this_month, last_reset FROM wp_themisdb_support_benefits WHERE license_id = {license_id}`
   - **Expected Result:**
     - `tickets_used_this_month = 0` (reset)
     - `last_reset = current datetime`

**Success Criteria:**
- ✅ Cron job executes without errors
- ✅ Monthly counters reset to 0
- ✅ Last reset timestamp updated

---

## Debug Commands

**Check database schema:**
```sql
DESCRIBE wp_themisdb_support_benefits;
```

**View all benefits:**
```sql
SELECT * FROM wp_themisdb_support_benefits;
```

**Check license-benefit relationship:**
```sql
SELECT 
    l.id, l.license_key, l.product_edition,
    b.id, b.tier_level, b.benefit_status,
    b.max_open_tickets, b.tickets_used_this_month
FROM wp_themisdb_licenses l
LEFT JOIN wp_themisdb_support_benefits b ON l.id = b.license_id;
```

**Check cron scheduled events:**
```php
// In WordPress admin or plugin:
var_dump(wp_next_scheduled('themisdb_support_benefits_monthly_reset'));
var_dump(wp_next_scheduled('themisdb_support_benefits_expiry_check'));
```

**Check error logs:**
```bash
tail -f /var/log/nginx/error.log  # or your WordPress error log
grep "Support ticket creation blocked" /var/log/debug.log
```

---

## Success Criteria Checklist

Run all test scenarios and verify:

- [ ] Test Scenario 1: Support benefits auto-created ✅
- [ ] Test Scenario 2: Benefits activated with license ✅
- [ ] Test Scenario 3: Ticket usage tracked ✅
- [ ] Test Scenario 4: Tier limits enforced ✅
- [ ] Test Scenario 5: Priority validation working ✅
- [ ] Test Scenario 6: Suspension blocks tickets ✅
- [ ] Test Scenario 7: Cancellation blocks tickets ✅
- [ ] Test Scenario 8: Cron jobs executing ✅

**All tests pass? → Phase 1.1 implementation is PRODUCTION READY ✅**

---

## Known Limitations / TODOs

- [ ] Email notifications not yet implemented (Phase 1 Bonus)
- [ ] Admin dashboard widgets not yet implemented (Phase 2+)
- [ ] API endpoints for customers not yet implemented (Phase 3+)

---

## Support

For issues encountered during testing:
1. Check WordPress debug.log
2. Review `class-support-benefits-manager.php` for error_log() entries
3. Verify database schema matches expected structure
4. Check that all 4 files properly integrated:
   - `class-support-benefits-manager.php` exists
   - `class-database.php` has support_benefits table
   - `class-license-manager.php` has 4 hooks
   - `class-ticket-manager.php` has validation
