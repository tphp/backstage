<?php

/**
 * This file is part of the tphp/tphp library
 *
 * @link        http://github.com/tphp/tphp
 * @copyright   Copyright (c) 2021 TPHP (http://www.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

class XCrypto
{
    function __construct($password = '')
    {
        $this->isOpenssl = true;
        if (defined('OPENSSL_RAW_DATA')) {
            $this->options = OPENSSL_RAW_DATA;
        } else {
            if (!function_exists('mcrypt_encrypt')) {
                // php必须包含其中一种扩展： openssl 或 mcrypt
                throw new Exception('Either Openssl or Mcrypt must be used');
            }
            $this->options = 1;
            $this->isOpenssl = false;
        }
        empty($password) && $password = 'NUM_XCR';
        $this->password = $password;
        $this->padPassword = $this->__getPadPassword();
    }

    /**
     * 补齐加密字符串
     * @return string
     */
    private function __getPadPassword() {
        $passwordLen = $this->password;
        if ($passwordLen >= 16) {
            return substr($this->password, 0, 16);
        }

        return str_pad($this->password, 16, "\0");
    }

    /**
     * 加密
     * @param string $input
     * @param string $method
     * @return string
     */
    private function __encrypt($input = '', $method = 'AES-128-ECB')
    {
        if ($this->isOpenssl) {
            $data = openssl_encrypt($input, $method, $this->password, $this->options);
        } else {
            if ($method == 'AES-128-ECB') {
                $cipher = MCRYPT_RIJNDAEL_128;
            } else {
                $cipher = MCRYPT_DES;
            }
            $block = @mcrypt_get_block_size($cipher, "ecb");
            $pad = $block - (strlen($input) % $block);
            $input .= str_repeat(chr($pad), $pad);
            $data = @mcrypt_encrypt($cipher, $this->padPassword, $input, MCRYPT_MODE_ECB);
        }
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 解密
     * @param string $input
     * @param string $method
     * @return string
     */
    private function __decrypt($input = '', $method = 'AES-128-ECB')
    {
        if ($this->isOpenssl) {
            $data = openssl_decrypt(base64_decode($input), $method, $this->password, $this->options);
        } else {
            if ($method == 'AES-128-ECB') {
                $cipher = MCRYPT_RIJNDAEL_128;
            } else {
                $cipher = MCRYPT_DES;
            }
            $input = base64_decode($input);
            $input = @mcrypt_decrypt($cipher, $this->padPassword, $input, MCRYPT_MODE_ECB);
            $len = strlen($input);
            $pad = ord($input[$len - 1]);
            $data = substr($input, 0, strlen($input) - $pad);
        }
        if ($data === false) {
            $data = '';
        }
        return $data;
    }


    /**
     * AES加密
     * @param $input
     * @return string
     * @throws
     */
    public function aesEncrypt($input)
    {
        return $this->__encrypt($input);
    }

    /**
     * AES解密
     * @param $input
     * @return string
     * @throws
     */
    public function aesDecrypt($input)
    {
        return $this->__decrypt($input);
    }

    public function desEncrypt($input = '')
    {
        return $this->__encrypt($input, 'DES-ECB');
    }

    public function desDecrypt($input = '')
    {
        return $this->__decrypt($input, 'DES-ECB');
    }
}