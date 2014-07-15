<?php

/**
 * The easy way to send MIME mails with PHP.
 *
 * @package   eric
 * @version   1.1.0-dev
 * @author    Jublo Solutions <support@jublo.net>
 * @copyright 2011-2014 Jublo Solutions <support@jublo.net>
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License 3.0
 * @link      https://github.com/jublonet/eric
 */
class Eric
{
    /**
     * MIME types safe to attach to an e-mail
     */
    static protected $_mime = array(
        'csv'  => 'text/comma-separated-values',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif'  => 'image/gif',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'mp3'  => 'audio/mpeg3',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'pps'  => 'application/vnd.ms-powerpoint',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rtf'  => 'text/rtf',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'txt'  => 'text/plain',
        'xls'  => 'application/msexcel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip'  => 'application/zip'
    );

    /**
     * Extensions sure to be blocked by e-mail clients like Outlook
     * (taken from Outlook 2010 list)
     */
    static protected $_block = array(
        'ade', 'adp', 'app', 'asp', 'bas', 'bat', 'cer', 'chm', 'cmd', 'cnt',
        'com', 'cpl', 'crt', 'csh', 'der', 'exe', 'fxp', 'grp', 'hlp', 'hpj',
        'hta', 'inf', 'ins', 'isp', 'its', 'jar', 'js', 'jse', 'ksh', 'lnk',
        'mad', 'maf', 'mag', 'mam', 'maq', 'mar', 'mas', 'mat', 'mau', 'mav',
        'maw', 'mcf', 'mda', 'mdb', 'mde', 'mdt', 'mdw', 'mdz', 'msc', 'msh',
        'msi', 'msp', 'mst', 'ops', 'osd', 'pcd', 'pif', 'pl', 'plg', 'prf',
        'prg', 'ps1', 'ps2', 'pst', 'reg', 'scf', 'scr', 'sct', 'shb', 'shs',
        'tmp', 'url', 'vb', 'vbe', 'vbp', 'vbs', 'vsw', 'ws', 'wsc', 'wsf',
        'wsh', 'xnk'
    );

    /**
     * Send out an e-mail to the given address with the given content
     */
    static public function send_mail($to, $subject, $content, $content_is_html,
        $from, $from_name, $files = null, $moreheaders = array()
    ) {
        // boundaries
        $outer = '0016e6dee7844eeb220498298a17';
        $inner = '0016e6dee7844eeb1c0498298a15';

        // Prepare default headers
        $message_id = '<'
            . sha1(time()) . '@'
            . str_replace('www.', '', $_SERVER['HTTP_HOST'])
            . '>';

        $headers = array(
            'Reply-To'                  => $from,
            'Return-Path'               => $from,
            'Subject'                   => trim($subject),
            'MIME-Version'              => '1.0',
            'Content-Transfer-Encoding' => '8bit',
            'Content-Type'              => 'multipart/mixed; boundary=' . $outer,
            'Message-ID'                => $message_id,
            'X-Mailer'                  => 'jublonet/eric',
        );
        // if running as non-cli, append URI
        if (isset($_SERVER['REQUEST_URI'])) {
            $script = $_SERVER['REQUEST_URI'];
            // don't send out secret parameters etc.
            if (strpos($script, '?') !== false) {
                $script = reset(explode('?', $script));
            }
            $headers['X-Mailer-Script'] = $script;
        }
        // merge custom headers to use
        $headers = array_merge($headers, $moreheaders);

        // get return path
        $return_path = $headers['Return-Path'];

        // encode headers
        $headers = self::encodeHeaders($headers);
        $headers['From'] = '=?utf-8?B?'
            . base64_encode($from_name)
            . '?= <' . $from . '>';

        // get Subject (now encoded)
        $subject = $headers['Subject'];
        unset($headers['Subject']);

        // collapse headers into string
        $headers = implode_assoc(': ', "\n", $headers);

        // convert CRLF to LF, wrap to 75 characters per line
        $content = str_replace("\r\n", "\n", $content);
        $content = wordwrap($content, 75);

        // start building the MIME
        $mime  = '--' . $outer . "\n";
        $mime .= 'Content-Type: multipart/alternative; boundary=' . $inner . "\n\n";

        // plaintext section
        $mime .= '--' . $inner . "\n";
        $mime .= "Content-Type: text/plain; charset=\"utf-8\"\n";
        $mime .= "Content-Transfer-Encoding: 8bit\n\n";

        // plaintext goes here
        $messagetext = $content;
        if ($content_is_html) {
            // remove style section first
            $messagetext = preg_replace('/<style[^>]+>[^<]+<\/style>/', '', $messagetext);

            // remove tags
            $messagetext = strip_tags($messagetext);

            // remove multiple empty lines
            $messagetext = preg_replace('/(\n)[ \t]+(\r?\n)/', '$1$2', $messagetext);
            $messagetext = preg_replace('/(\r?\n){3,}/', '$1$1', $messagetext);
        }
        $mime .= $messagetext . "\n\n";

        // html section
        $mime .= "\n--" . $inner . "\n";
        $mime .= "Content-Type: text/html; charset=\"utf-8\"\n";
        $mime .= "Content-Transfer-Encoding: 8bit\n\n";

        // html goes here
        $messagehtml = $content;
        if (! $content_is_html) {
            // format the text-only properly for HTML
            $messagehtml = nl2br(htmlspecialchars($messagehtml));
        }
        $mime .= $messagehtml . "\n";
        $mime .= "\n--" . $inner . "--\n";

        // add attachments
        if ($files !== null) {

            // for each attachment
            foreach ($files as $file) {

                // check if file exists
                if (! file_exists($file)) {
                    trigger_error(
                        'The e-mail attachment ' . $file[1]
                        . ' does not exist.',
                        E_USER_ERROR
                    );
                }

                $inline = 0;
                // embed files in html using optional third array parameter
                if (count($file) > 2 && $file[2]) {
                    $inline = 1;
                }
                $filename = basename($file[0]);

                $mime .= "\n--" . $outer . "\n";

                // write correct MIME type
                $mime .= 'Content-Type: ';

                // get extension, if any
                $mime = 'application/octetstream';
                $extension = strrpos($filename, '.');
                if ($extension > -1) {
                    $extension = strtolower(substr($filename, $extension + 1));

                    // check for blocked extensions
                    if (in_array($extension, self::$_blocked)) {
                        trigger_error(
                            'The e-mail attachment ' . $file[1]
                            . ' will be blocked by e-mail clients.',
                            E_USER_WARNING
                        );
                    }

                    if (in_array($extension, array_keys(self::$_mime))) {
                        $mime = self::$_mime[$extension];
                    }
                }

                $mime .= ";\n\tname=\"" . $file[1] . "\"\n";
                $mime .= "Content-Transfer-Encoding: base64\n";

                if ($inline) {

                    // add inline content ID
                    $mime .= "Content-ID: <" . $file[1] . ">\n";
                    $mime .= "Content-Disposition: inline;\n\tfilename=\"" . $file[1] .
                        "\"";
                } else {

                    // add attachment name
                    $mime .= "Content-Disposition: attachment;\n\tfilename=\"" .
                        $file[1] . "\"";
                }

                $mime .= "\n\n";

                // file goes here
                $data = @file_get_contents($file[0]);

                // encode the contents
                $data = chunk_split(base64_encode($data));

                // add it to the message
                $mime .= $data;
                $mime .= "\n\n";

            }
        }

        // message ends
        $mime .= "\n--" . $outer . "--\n";

        $result = mail($to, $subject, $mime, $headers, '-f' . $return_path);

        // on failure, send bounce
        if ($result !== true) {
            mail(
                $return_path, 'Not delivered: ' . $subject,
                "The following message was not delivered to $to:\r\n\r\n$text",
                $headers
            );
        }
    }

    /**
     * Encodes email headers as per RFC2047.
     *
     * @param array $input The header data to encode
     *
     * @return array Encoded headers
     */
    static private function encodeHeaders($input)
    {
        foreach ($input as $hdr_name => $hdr_value) {
            if (preg_match('#([\x80-\xFF]){1}#', $hdr_value)) {
                // Check if there is a double quote at beginning or end of
                // the string to prevent that an open or closing quote gets
                // ignored because it is encapsuled by an encoding pre/suffix.
                // Remove the double quote and set the specific prefix or
                // suffix variable so that we can concat the encoded string and
                // the double quotes back together to get the intended string.
                $quotePrefix = $quoteSuffix = '';
                if ($hdr_value{0} == '"') {
                    $hdr_value = substr($hdr_value, 1);
                    $quotePrefix = '"';
                }
                if ($hdr_value{strlen($hdr_value) - 1} == '"') {
                    $hdr_value = substr($hdr_value, 0, -1);
                    $quoteSuffix = '"';
                }

                // Generate the header using the specified params and dynamically
                // determine the maximum length of such strings.
                // 75 is the value specified in the RFC. The -2 is there so
                // the later regexp doesn't break any of the translated chars.
                // The -2 on the first line-regexp is to compensate for the ": "
                // between the header-name and the header value
                $prefix = '=?UTF-8?Q?';
                $suffix = '?=';
                $maxLength = 75 - strlen($prefix . $suffix) - 2 - 1;
                $maxLength1stLine = $maxLength - strlen($hdr_name) - 2;
                $maxLength = $maxLength - 1;

                // Replace all special characters used by the encoder.
                $search = array('=', '_', '?', ' ');
                $replace = array('=3D', '=5F', '=3F', '_');
                $hdr_value = str_replace($search, $replace, $hdr_value);

                // Replace all extended characters (\x80-xFF) and non-printable
                // characters with their ASCII values.
                $hdr_value = preg_replace('#([\x00-\x1F\x80-\xFF])#e',
                    '"=" . strtoupper(str_pad(dechex(ord("\1")), 2, "0", STR_PAD_LEFT))',
                    $hdr_value);

                // This regexp will break QP-encoded text at every $maxLength
                // but will not break any encoded letters.
                $reg1st = "|(.{0,$maxLength1stLine}[^\=][^\=])|";
                $reg2nd = "|(.{0,$maxLength}[^\=][^\=])|";

                // Concat the double quotes and encoded string together
                $hdr_value = $quotePrefix . $hdr_value . $quoteSuffix;

                $hdr_value_out = $hdr_value;
                $realMax = $maxLength1stLine + strlen($prefix . $suffix);
                if (strlen($hdr_value_out) >= $realMax) {
                    // Begin with the regexp for the first line.
                    $reg = $reg1st;
                    $output = "";
                    while ($hdr_value_out) {
                        // Split translated string at every $maxLength
                        // But make sure not to break any translated chars.
                        $found = preg_match($reg, $hdr_value_out, $matches);

                        // After this first line, we need to use a different
                        // regexp for the first line.
                        $reg = $reg2nd;

                        // Save the found part and encapsulate it in the
                        // prefix & suffix. Then remove the part from the
                        // $hdr_value_out variable.
                        if ($found) {
                            $part = $matches[0];
                            $len = strlen($matches[0]);
                            $hdr_value_out = substr($hdr_value_out, $len);
                        }
                        else {
                            $part = $hdr_value_out;
                            $hdr_value_out = "";
                        }

                        // RFC 2047 specifies that any split header should
                        // be seperated by a CRLF SPACE
                        if ($output) {
                            $output .= "\r\n ";
                        }
                        $output .= $prefix . $part . $suffix;
                    }
                    $hdr_value_out = $output;
                }
                else {
                    $hdr_value_out = $prefix . $hdr_value_out . $suffix;
                }
                $hdr_value = $hdr_value_out;
            }
            $input[$hdr_name] = $hdr_value;
        }
        return $input;
    }
}

if (! function_exists('implode_assoc')) {
    /**
     * Implode an associative array.
     *
     * Implodes an array into the form:
     *
     * [key1][inner_glue][value1][outer_glue][key2][inner_glue][value2]...
     */
    function implode_assoc($inner_glue, $outer_glue, $array)
    {
        $output = array();
        foreach ($array as $key => $item) $output[] = $key . $inner_glue . $item;

        return implode($outer_glue, $output);
    }
}
