# Analog Fan - Test Credentials & Documentation

## Test User Credentials

### User 1 (Buyer/Seller)
**Note:** This user can both buy and sell items.
- **Username:** `_________________`
- **Password:** `_________________`

### User 2 (Buyer/Seller)
**Note:** This user can both buy and sell items.
- **Username:** `_________________`
- **Password:** `_________________`

### Admin
- **Username:** `_________________`
- **Password:** `_________________`

---

## Shipment Status Testing Feature

### The Challenge
Sendcloud does not provide a testing environment for webhooks, and simulating webhook calls with Postman would make the testing process uncomfortable and require manual work for each status change.

### The Solution
I implemented a **Mock Webhook Testing Interface** directly in the application. On the **My Sales** page, each shipment has a testing control panel where you can select a delivery status and click "Update" to simulate a webhook call. The status updates in real-time, just as it would in production.

---

## Additional Notes
- All users (buyer/seller roles) have the ability to both purchase and sell items on the platform
- Shipments are automatically created when orders are marked as paid
- Email notifications are sent to buyers and sellers at various stages of the order process. To test this functionality, you will need to create new users with valid email addresses or update existing user email addresses