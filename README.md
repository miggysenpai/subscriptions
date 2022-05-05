# Subscriptions
Userspice with stripe API.

To install, please use a fresh copy of userspice. This is the membership plugin that has been modified and update to work with stripe API
- Drop the membership folder in usersc/plugin 
- Go to stripe and get your api keys
- Also, in stripe create a webhook and add an endpoint with the URL of `https://yourdomain.com/usersc/plugins/subscriptions/StripeWebhook.php?webhook=webhook` and to listen to "invoice.payment_succeeded" in event types
- subscription.php should copy to usersc/ on install, but if not, its located in plugins/membership/files.
  - Drop subscription.php in usersc/

subscription.php will be the main page where users can...
  - See information about their subscription
  - Update their subscription
  - Cancel their subscription
  - View payment methods
  - Delete payment methods
  - View invoices
  - Listing all balance transactions 

In the configuration page you can...
  - Add levels/ pricing options
  - View Existing plans/levels
  - Edit stripe keys
  - Payouts (yassssss)
  - Refunds(view/ refund)
  - Coupons (Create/Delete/View)
  - Coupons (on/off setting) 
  - List Invoices 

As of right now, this plugin is fully functioning, but there might be future updates. Future updates will include the following feautures. 
  - Option to email customer for a failed payment


Also, I will clean up the code a little, its a bit messy. I at the time do not have much more to add to the plugin as stripe api can be limiting. but like said, it is fully functional. If there are any bugs, do notify me on discord. Miggy#3221, Thanks.
