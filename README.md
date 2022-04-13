# Subscriptions
Userspice with stripe API.

To install, please use a fresh copy of userspice. This is the membership plugin that has been modified and update to work with stripe API
- Drop the membership folder in usersc/plugin 
- Go to stripe and get your api keys
- Also, in stripe create a webhook and add an endpoint with the URL of "https://yourdomain.com/usersc/plugins/membership/StripeWebhook.php?webhook=webhook" and to listen to "invoice.payment_succeeded" in event types
- membership.php should copy to usersc/ on install, but if not, its located in plugins/membership/files.
  - Drop membership.php in usersc/

membership.php will be the main page where users can...
  - See information about their subscription
  - Update their subscription
  - Cancel their subscription
  - View payment methods
  - Edit payment methods
  - Delete payment methods
  - View invoices
  - Listing all balance transactions 
  - Payouts (yassssss)
  - Refunds
  - Coupons (Create/Delete/View) 
  - List Invoices 

Future updates will include the following feautures. This will be in the admin page, so the configure.php file will get an update. As of right now, it looks a little ugly, so it will get a css update to try to clean things up and make it look more like a "admin dashboard."
- Better listing current stripe subscriptions
- Better listing on current stripe customers
- Better listing on current stripe Products/prices


Also, I will clean up the code a little, its a bit messy. 
