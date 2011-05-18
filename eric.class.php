<?php

/**
 * Eric
 *
 * @package eric
 * @author jublonet
 * @link https://github.com/jublonet/eric
 * @copyright 2011
 * @access public
 */
class Eric
{
    /**
     * This sends out an e-mail to the given address with the given content.
     */
    static public function send_mail($to, $subject, $message, $content, $from, $from_name,
        $files = null, $moreheaders = array())
    {
        $OB = "0016e6dee7844eeb220498298a17";
        $IB = "0016e6dee7844eeb1c0498298a15";

        // Get some settings
        $name = $from_name;
        $returnPath = $from;

        // Prepare headers
        $headers = array();
        $headers['Reply-To'] = $from;
        $headers['Return-Path'] = $returnPath;
        $headers['Subject'] = trim($subject);
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Transfer-Encoding'] = '8bit';
        $headers['Content-Type'] = 'multipart/mixed; boundary=' . $OB;
        $headers['Message-ID'] = '<' . sha1(time()) . '@' . str_replace('www.',
            '', $_SERVER['HTTP_HOST']) . '>';
        $headers['X-Mailer'] = 'jublonet/eric';
        if (isset($_SERVER['REQUEST_URI'])) {
            $strScript = $_SERVER['REQUEST_URI'];
            // dont send out secret parameters etc.
            if (strpos($strScript, '?') !== false) {
                $strScript = reset(explode('?', $strScript));
            }
            $headers['X-Mailer-Script'] = $strScript;
        }
        $headers = array_merge($moreheaders, $headers);

        // Encode headers
        $headers = self::encodeHeaders($headers);
        $headers['From'] = utf8_decode($name) . ' <' . $from . '>';

        // Get Subject
        $subject = $headers['Subject'];
        unset($headers['Subject']);

        // Collapse headers into string
        $headers = implode_assoc(': ', "\n", $headers);

        // Reformat message
        $message = str_replace("\r\n", "\n", $message);
        $message = wordwrap($message, 75);

        $mime = '--' . $OB . "\n";
        $mime .= 'Content-Type: multipart/alternative; boundary=' . $IB . "\n\n";

        // plaintext section
        $mime .= '--' . $IB . "\n";
        $mime .= "Content-Type: text/plain; charset=\"utf-8\"\n";
        $mime .= "Content-Transfer-Encoding: 8bit\n\n";
        // plaintext goes here
        $messagetext = $message;
        if ($content) {
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
        $mime .= "\n--" . $IB . "\n";
        $mime .= "Content-Type: text/html; charset=\"utf-8\"\n";
        $mime .= "Content-Transfer-Encoding: 8bit\n\n";

        // html goes here
        $messagehtml = $message;
        if (!$content) {
            // format the text-only properly for HTML
            $messagehtml = nl2br(htmlentities(utf8_decode($messagehtml)));
        }
        $mime .= $messagehtml . "\n";
        $mime .= "\n--" . $IB . "--\n";

        // add attachments
        if ($files !== null) {

            // for each attachment
            foreach ($files as $attmFile) {

                $inline = 0;
                // embed files in html using optional third array parameter
                if (count($attmFile) > 2 && $attmFile[2]) {
                    $inline = 1;
                }
                $patharray = explode("/", $attmFile[0]);
                $fileName = $patharray[count($patharray) - 1];

                $mime .= "\n--" . $OB . "\n";

                $mime .= "Content-Type: ";
                if (substr($fileName, -3) == "gif") $mime .= "image/gif";
                elseif (substr($fileName, -3) == "jpg") $mime .= "image/jpeg";
                elseif (substr($fileName, -3) == "png") $mime .= "image/png";
                else  $mime .= "application/octetstream";

                $mime .= ";\n\tname=\"" . $attmFile[1] . "\"\n";
                $mime .= "Content-Transfer-Encoding: base64\n";
                if ($inline) {
                    $mime .= "Content-ID: <" . $attmFile[1] . ">\n";
                    $mime .= "Content-Disposition: inline;\n\tfilename=\"" . $attmFile[1] .
                        "\"";
                }
                else {
                    $mime .= "Content-Disposition: attachment;\n\tfilename=\"" .
                        $attmFile[1] . "\"";
                }

                $mime .= "\n\n";

                // file goes here
                $fd = @fopen($attmFile[0], "r");
                $fileContent = "";

                while (!feof($fd)) {
                    $fileContent .= fgets($fd, 1024);
                }

                @fclose($fd);

                // encode the contents
                $fileContent = chunk_split(base64_encode($fileContent));

                // add it to the message
                $mime .= $fileContent;
                $mime .= "\n\n";

            }
        }

        // message ends
        $mime .= "\n--" . $OB . "--\n";

        if (!mail($to, $subject, $mime, $headers, '-f' . $returnPath)) {
            mail($returnPath, 'Not delivered: ' . $subject,
                "The following message was not delivered to $to:\r\n\r\n$text",
                $headers);
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

if (!function_exists('implode_assoc')) {
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
