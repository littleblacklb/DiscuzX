<?php

namespace OSS\Credentials;

use OSS\Core\OssException;

class EnvironmentVariableCredentialsProvider implements CredentialsProvider {

	/**
	 * @return Credentials
	 * @throws OssException
	 */
	public function getCredentials() {
		$ak = defined('OSS_ACCESS_KEY_ID') ? OSS_ACCESS_KEY_ID : '';
		$sk = defined('OSS_ACCESS_KEY_SECRET') ? OSS_ACCESS_KEY_SECRET : '';
		$token = defined('OSS_SESSION_TOKEN') ? OSS_SESSION_TOKEN : '';
		return new Credentials($ak, $sk, $token);
	}
}
