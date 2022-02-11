<?php

namespace FTPDeploy\Inc;

use Exception;

class FTPD
{
    /**
     * @var resource ftp
     */
    private $ftp;

    public function __construct(
        private $server,
        private $username,
        private $password
    ) {
    }

    public function connect()
    {
        $this->ftp = ftp_ssl_connect($this->server);

        if ($this->ftp) {
            ftp_login($this->ftp, $this->username, $this->password);
            ftp_pasv($this->ftp, true);
        } else {
            throw new Exception('FTP Connect falied');
        }
    }

    /**
     * @param string Source for item in to server
     */
    public function deleteItem($item)
    {
        if ($this->dirExists($item))
            $call = 'ftp_rmdir';
        else
            $call = 'ftp_delete';

        return @call_user_func($call, $this->ftp, $item);
    }

    public function dirExists($item)
    {
        return @ftp_nlist($this->ftp, $item) !== false;
    }

    /**
     * @param string $from Local file
     * @param string $to Server dir
     */
    public function createItem($from, $to)
    {
        if (!@$this->dirExists($to)) {
            $this->mkdir($this->ftp, $to);
        }

        $filename = basename($from);
        $to = rtrim($to, '/');

        if (!@ftp_put($this->ftp, $to . "/" . $filename, $from))
            error_log("FTP put failed upload file: $from in $to");
    }

    public function close()
    {
        ftp_close($this->ftp);
    }

    /**
     * @param resource $ftp The ftp resource
     * @param string $ftpdir The dir name
     */
    public function mkdir($ftp, $ftpdir)
    {
        $dirs = array_filter(explode('/', $ftpdir), function ($e) {
            return trim($e) != '';
        });

        foreach ($dirs as $dir) {
            if (!@ftp_chdir($ftp, $dir)) {
                ftp_mkdir($ftp, $dir);
                ftp_chdir($ftp, $dir);
            }
        }
    }
}
