# Payslip downloader

This script will download your payslips from pscpayroll.com

## How it works?

It uses your credentials to login to the employee portal, accesses the list of payslips 
and compares it with the list stored in payslipHistory.json. If the payslip ID is not 
on the list, a PDF file will be downloaded and saved in _payslips_ directory.

## Use cases

- Download and print payslips automatically
- Archive the payslips in your own storage for easy access
- E-mail payslips somewhere, as the portal doesn't to that

## How to use

1. Clone it (obviously)
2. Modify the `payslip_export.php` - change those lines:

```
define('USERNAME', 'PUT_YOUR_EMAIL_HERE');
define('PASSWORD', 'PUT_YOUR_PASSWORD_HERE');
```

3. Set up a cron or something. You're a big boi, figure it yourself.
4. ...
5. Profit.

If you want the script to do something extra, the end of the `foreach` loop under 
the `Export payslips` is probably your place to go.

## Why is this in PHP?!!!1one

If you don't like it, write it yourself.

## License

Feel free to modify this script however you want. I'm not responsible for what you do with it. 
I'm not responsible, if you spam the site and the PSC changes terms of service, blocks this and hunts you down.

This was made, because I'm lazy and have trust issues.

I'm not associated with PSC in any way.
