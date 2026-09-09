<?php
function get_email_template($subject, $body_content, $btn_text = '', $btn_link = '', $unsubscribe_link = '')
{

    $site_name = defined('SITE_NAME') ? SITE_NAME : 'News Portal';
    $year = date('Y');

    $button_html = '';
    if (!empty($btn_text) && !empty($btn_link)) {
        $button_html = "
        <tr>
            <td align='center' style='padding: 30px 0;'>
                <a href='$btn_link' style='background-color: #4f46e5; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;'>
                    $btn_text
                </a>
            </td>
        </tr>";
    }

    $footer_html = "";
    if (!empty($unsubscribe_link)) {
        $footer_html = "
        <p style='margin: 0; font-size: 12px; color: #9ca3af;'>
            Don't want to receive these emails? 
            <a href='$unsubscribe_link' style='color: #4f46e5; text-decoration: underline;'>Unsubscribe here</a>.
        </p>";
    }

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$subject</title>
        <style>
            body { margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
            img { max-width: 100%; height: auto; }
            a { color: #4f46e5; }
        </style>
    </head>
    <body style='background-color: #f3f4f6; padding: 40px 0;'>
        
        <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);'>
            
            <!-- HEADER -->
            <tr>
                <td align='center' style='background-color: #1e1b4b; padding: 30px;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px;'>$site_name</h1>
                </td>
            </tr>

            <!-- BODY -->
            <tr>
                <td style='padding: 40px 40px 20px 40px;'>
                    <h2 style='color: #111827; margin-top: 0; font-size: 20px;'>$subject</h2>
                    <div style='color: #4b5563; line-height: 1.6; font-size: 16px;'>
                        $body_content
                    </div>
                </td>
            </tr>

            <!-- OPTIONAL BUTTON -->
            $button_html

            <!-- FOOTER -->
            <tr>
                <td style='background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;'>
                    <p style='margin: 0 0 10px 0; font-size: 12px; color: #6b7280;'>
                        &copy; $year $site_name. All rights reserved.<br>
                        Developed by <a href='https://digitalcreatorss.com' style='color:#4f46e5;text-decoration:none;'>Digital Creatorss</a>
                    </p>
                    $footer_html
                </td>
            </tr>
        </table>

        <!-- Spacer for Mobile -->
        <table align='center' border='0' cellpadding='0' cellspacing='0' width='600'>
            <tr><td height='40'></td></tr>
        </table>

    </body>
    </html>
    ";
}
