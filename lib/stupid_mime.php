<?php
# File: stupid_mime.php
# This script is a poor substitute for a proper MIME type library.  If the server
# has mime_magic or finfo, these are used.  If not, LnBlog defaults to this.
#
# This script does simple, brain-dead file extension checking.  It contains a
# list of known file extensions and corresponding MIME types and matches them
# up.  If a file uses a non-standard extension, then the reported MIME type
# *will* be wrong.

# Function: stupid_mime_get_type
# This just matches possible extensions on a file name to a MIME type.  This is totally
# the wrong way to do this, but writing a full MIME type library is outside the
# scope of this project.

function stupid_mime_get_type($filename) {
    $exts = stupid_mime_get_extensions($filename);
    $type = 'application/octet-stream'; # Default for unknown type.
    foreach ($exts as $ext) {
        switch ($ext) {

            # Add extra MIME types here.
            case 'wmv': $type = 'video/x-ms-wmv'; break;
            case 'wma': $type = 'audio/x-ms-wma'; break;

            # The follwing list taken from http://www.w3schools.com/media/media_mimeref.asp
            case '323': $type = 'text/h323'; break;
            case 'acx': $type = 'application/internet-property-stream'; break;
            case 'ai': $type = 'application/postscript'; break;
            case 'aif': $type = 'audio/x-aiff'; break;
            case 'aifc': $type = 'audio/x-aiff'; break;
            case 'aiff': $type = 'audio/x-aiff'; break;
            case 'asf': $type = 'video/x-ms-asf'; break;
            case 'asr': $type = 'video/x-ms-asf'; break;
            case 'asx': $type = 'video/x-ms-asf'; break;
            case 'au': $type = 'audio/basic'; break;
            case 'avi': $type = 'video/x-msvideo'; break;
            case 'axs': $type = 'application/olescript'; break;
            case 'bas': $type = 'text/plain'; break;
            case 'bcpio': $type = 'application/x-bcpio'; break;
            case 'bin': $type = 'application/octet-stream'; break;
            case 'bmp': $type = 'image/bmp'; break;
            case 'c': $type = 'text/plain'; break;
            case 'cat': $type = 'application/vnd.ms-pkiseccat'; break;
            case 'cdf': $type = 'application/x-cdf'; break;
            case 'cer': $type = 'application/x-x509-ca-cert'; break;
            case 'class': $type = 'application/octet-stream'; break;
            case 'clp': $type = 'application/x-msclip'; break;
            case 'cmx': $type = 'image/x-cmx'; break;
            case 'cod': $type = 'image/cis-cod'; break;
            case 'cpio': $type = 'application/x-cpio'; break;
            case 'crd': $type = 'application/x-mscardfile'; break;
            case 'crl': $type = 'application/pkix-crl'; break;
            case 'crt': $type = 'application/x-x509-ca-cert'; break;
            case 'csh': $type = 'application/x-csh'; break;
            case 'css': $type = 'text/css'; break;
            case 'dcr': $type = 'application/x-director'; break;
            case 'der': $type = 'application/x-x509-ca-cert'; break;
            case 'dir': $type = 'application/x-director'; break;
            case 'dll': $type = 'application/x-msdownload'; break;
            case 'dms': $type = 'application/octet-stream'; break;
            case 'doc': $type = 'application/msword'; break;
            case 'dot': $type = 'application/msword'; break;
            case 'dvi': $type = 'application/x-dvi'; break;
            case 'dxr': $type = 'application/x-director'; break;
            case 'eps': $type = 'application/postscript'; break;
            case 'etx': $type = 'text/x-setext'; break;
            case 'evy': $type = 'application/envoy'; break;
            case 'exe': $type = 'application/octet-stream'; break;
            case 'fif': $type = 'application/fractals'; break;
            case 'flr': $type = 'x-world/x-vrml'; break;
            case 'gif': $type = 'image/gif'; break;
            case 'gtar': $type = 'application/x-gtar'; break;
            case 'gz': $type = 'application/x-gzip'; break;
            case 'h': $type = 'text/plain'; break;
            case 'hdf': $type = 'application/x-hdf'; break;
            case 'hlp': $type = 'application/winhlp'; break;
            case 'hqx': $type = 'application/mac-binhex40'; break;
            case 'hta': $type = 'application/hta'; break;
            case 'htc': $type = 'text/x-component'; break;
            case 'htm': $type = 'text/html'; break;
            case 'html': $type = 'text/html'; break;
            case 'htt': $type = 'text/webviewhtml'; break;
            case 'ico': $type = 'image/x-icon'; break;
            case 'ief': $type = 'image/ief'; break;
            case 'iii': $type = 'application/x-iphone'; break;
            case 'ins': $type = 'application/x-internet-signup'; break;
            case 'isp': $type = 'application/x-internet-signup'; break;
            case 'jfif': $type = 'image/pipeg'; break;
            case 'jpe': $type = 'image/jpeg'; break;
            case 'jpeg': $type = 'image/jpeg'; break;
            case 'jpg': $type = 'image/jpeg'; break;
            case 'js': $type = 'application/x-javascript'; break;
            case 'latex': $type = 'application/x-latex'; break;
            case 'lha': $type = 'application/octet-stream'; break;
            case 'lsf': $type = 'video/x-la-asf'; break;
            case 'lsx': $type = 'video/x-la-asf'; break;
            case 'lzh': $type = 'application/octet-stream'; break;
            case 'm13': $type = 'application/x-msmediaview'; break;
            case 'm14': $type = 'application/x-msmediaview'; break;
            case 'm3u': $type = 'audio/x-mpegurl'; break;
            case 'man': $type = 'application/x-troff-man'; break;
            case 'mdb': $type = 'application/x-msaccess'; break;
            case 'me': $type = 'application/x-troff-me'; break;
            case 'mht': $type = 'message/rfc822'; break;
            case 'mhtml': $type = 'message/rfc822'; break;
            case 'mid': $type = 'audio/mid'; break;
            case 'mny': $type = 'application/x-msmoney'; break;
            case 'mov': $type = 'video/quicktime'; break;
            case 'movie': $type = 'video/x-sgi-movie'; break;
            case 'mp2': $type = 'video/mpeg'; break;
            case 'mp3': $type = 'audio/mpeg'; break;
            case 'mpa': $type = 'video/mpeg'; break;
            case 'mpe': $type = 'video/mpeg'; break;
            case 'mpeg': $type = 'video/mpeg'; break;
            case 'mpg': $type = 'video/mpeg'; break;
            case 'mpp': $type = 'application/vnd.ms-project'; break;
            case 'mpv2': $type = 'video/mpeg'; break;
            case 'ms': $type = 'application/x-troff-ms'; break;
            case 'mvb': $type = 'application/x-msmediaview'; break;
            case 'nws': $type = 'message/rfc822'; break;
            case 'oda': $type = 'application/oda'; break;
            case 'p10': $type = 'application/pkcs10'; break;
            case 'p12': $type = 'application/x-pkcs12'; break;
            case 'p7b': $type = 'application/x-pkcs7-certificates'; break;
            case 'p7c': $type = 'application/x-pkcs7-mime'; break;
            case 'p7m': $type = 'application/x-pkcs7-mime'; break;
            case 'p7r': $type = 'application/x-pkcs7-certreqresp'; break;
            case 'p7s': $type = 'application/x-pkcs7-signature'; break;
            case 'pbm': $type = 'image/x-portable-bitmap'; break;
            case 'pdf': $type = 'application/pdf'; break;
            case 'pfx': $type = 'application/x-pkcs12'; break;
            case 'pgm': $type = 'image/x-portable-graymap'; break;
            case 'pko': $type = 'application/ynd.ms-pkipko'; break;
            case 'pma': $type = 'application/x-perfmon'; break;
            case 'pmc': $type = 'application/x-perfmon'; break;
            case 'pml': $type = 'application/x-perfmon'; break;
            case 'pmr': $type = 'application/x-perfmon'; break;
            case 'pmw': $type = 'application/x-perfmon'; break;
            case 'pnm': $type = 'image/x-portable-anymap'; break;
            case 'pot,': $type = 'application/vnd.ms-powerpoint'; break;
            case 'ppm': $type = 'image/x-portable-pixmap'; break;
            case 'pps': $type = 'application/vnd.ms-powerpoint'; break;
            case 'ppt': $type = 'application/vnd.ms-powerpoint'; break;
            case 'prf': $type = 'application/pics-rules'; break;
            case 'ps': $type = 'application/postscript'; break;
            case 'pub': $type = 'application/x-mspublisher'; break;
            case 'qt': $type = 'video/quicktime'; break;
            case 'ra': $type = 'audio/x-pn-realaudio'; break;
            case 'ram': $type = 'audio/x-pn-realaudio'; break;
            case 'ras': $type = 'image/x-cmu-raster'; break;
            case 'rgb': $type = 'image/x-rgb'; break;
            case 'rmi': $type = 'audio/mid'; break;
            case 'roff': $type = 'application/x-troff'; break;
            case 'rtf': $type = 'application/rtf'; break;
            case 'rtx': $type = 'text/richtext'; break;
            case 'scd': $type = 'application/x-msschedule'; break;
            case 'sct': $type = 'text/scriptlet'; break;
            case 'setpay': $type = 'application/set-payment-initiation'; break;
            case 'setreg': $type = 'application/set-registration-initiation'; break;
            case 'sh': $type = 'application/x-sh'; break;
            case 'shar': $type = 'application/x-shar'; break;
            case 'sit': $type = 'application/x-stuffit'; break;
            case 'snd': $type = 'audio/basic'; break;
            case 'spc': $type = 'application/x-pkcs7-certificates'; break;
            case 'spl': $type = 'application/futuresplash'; break;
            case 'src': $type = 'application/x-wais-source'; break;
            case 'sst': $type = 'application/vnd.ms-pkicertstore'; break;
            case 'stl': $type = 'application/vnd.ms-pkistl'; break;
            case 'stm': $type = 'text/html'; break;
            case 'sv4cpio': $type = 'application/x-sv4cpio'; break;
            case 'sv4crc': $type = 'application/x-sv4crc'; break;
            case 't': $type = 'application/x-troff'; break;
            case 'tar': $type = 'application/x-tar'; break;
            case 'tcl': $type = 'application/x-tcl'; break;
            case 'tex': $type = 'application/x-tex'; break;
            case 'texi': $type = 'application/x-texinfo'; break;
            case 'texinfo': $type = 'application/x-texinfo'; break;
            case 'tgz': $type = 'application/x-compressed'; break;
            case 'tif': $type = 'image/tiff'; break;
            case 'tiff': $type = 'image/tiff'; break;
            case 'tr': $type = 'application/x-troff'; break;
            case 'trm': $type = 'application/x-msterminal'; break;
            case 'tsv': $type = 'text/tab-separated-values'; break;
            case 'txt': $type = 'text/plain'; break;
            case 'uls': $type = 'text/iuls'; break;
            case 'ustar': $type = 'application/x-ustar'; break;
            case 'vcf': $type = 'text/x-vcard'; break;
            case 'vrml': $type = 'x-world/x-vrml'; break;
            case 'wav': $type = 'audio/x-wav'; break;
            case 'wcm': $type = 'application/vnd.ms-works'; break;
            case 'wdb': $type = 'application/vnd.ms-works'; break;
            case 'wks': $type = 'application/vnd.ms-works'; break;
            case 'wmf': $type = 'application/x-msmetafile'; break;
            case 'wps': $type = 'application/vnd.ms-works'; break;
            case 'wri': $type = 'application/x-mswrite'; break;
            case 'wrl': $type = 'x-world/x-vrml'; break;
            case 'wrz': $type = 'x-world/x-vrml'; break;
            case 'xaf': $type = 'x-world/x-vrml'; break;
            case 'xbm': $type = 'image/x-xbitmap'; break;
            case 'xla': $type = 'application/vnd.ms-excel'; break;
            case 'xlc': $type = 'application/vnd.ms-excel'; break;
            case 'xlm': $type = 'application/vnd.ms-excel'; break;
            case 'xls': $type = 'application/vnd.ms-excel'; break;
            case 'xlt': $type = 'application/vnd.ms-excel'; break;
            case 'xlw': $type = 'application/vnd.ms-excel'; break;
            case 'xof': $type = 'x-world/x-vrml'; break;
            case 'xpm': $type = 'image/x-xpixmap'; break;
            case 'xwd': $type = 'image/x-xwindowdump'; break;
            case 'z': $type = 'application/x-compress'; break;
            case 'zip': $type = 'application/zip'; break;

            default: $type = 'application/octet-stream';
        }
    }
    return $type;
}

# Function: stupid_mime_get_extensions
# This takes a filename and extracts a list of all possible file extensions.
# This function supports <stupid_mime_get_type>.

function stupid_mime_get_extensions($filename) {
    $ret = array();
    while ( strpos($filename, ".") > 0 &&
            strpos($filename, ".") < strlen($filename)-1 ) {
        $filename = substr($filename, strpos($filename,".") + 1);
        $ret[] = $filename;
    }
    return $ret;
}
