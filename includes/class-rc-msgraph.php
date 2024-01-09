<?php
/**
 * Class file for interacting with Microsoft Graph API
 *
 * @package RawConscious
 */

use Microsoft\Graph\Graph;
use Microsoft\Graph\Http;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;
/**
 * Class for interacting with Microsoft Graph API
 */
class RC_MSGraph {
	/**
	 * Token client
	 *
	 * @var Client $token_client .
	 */
	private static Client $token_client;

	/**
	 * App Token
	 *
	 * @var string $app_token token client.
	 */
	private static string $app_token;

	/**
	 * Client Id
	 *
	 * @var string $client_id .
	 */
	private static string $client_id = '';

	/**
	 * Client Secret
	 *
	 * @var string $client_secret .
	 */
	private static string $client_secret;

	/**
	 * Tenant Id
	 *
	 * @var string $tenant_id .
	 */
	private static string $tenant_id;
	/**
	 * App Client
	 *
	 * @var Graph $app_client .
	 */
	private static Graph $app_client;

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function initialize(): void {
		// phpcs disabled to override warnings related to unsanitized environment variables.
		// phpcs:disable
		self::$token_client  = new Client();
		self::$client_id     = isset( $_ENV['CLIENT_ID'] ) ? $_ENV['CLIENT_ID'] : '';
		self::$client_secret = isset( $_ENV['CLIENT_SECRET'] ) ? $_ENV['CLIENT_SECRET'] : '';
		self::$tenant_id     = isset( $_ENV['TENANT_ID'] ) ? $_ENV['TENANT_ID'] : '';
		self::$app_client    = new Graph();
		// phpcs:enable
	}

	/**
	 * Get Token
	 *
	 * @return string
	 * @throws Exception Throws http error.
	 */
	public static function get_app_only_token(): string {
		if ( isset( self::$app_token ) ) {
			return self::$app_token;
		}

		$token_request_url = 'https://login.microsoftonline.com/' . self::$tenant_id . '/oauth2/v2.0/token';

		$token_response = self::$token_client->post(
			$token_request_url,
			array(
				'form_params' => array(
					'client_id'     => self::$client_id,
					'client_secret' => self::$client_secret,
					'grant_type'    => 'client_credentials',
					'scope'         => 'https://graph.microsoft.com/.default',
				),

				'http_errors' => false,
				'curl'        => array(
					CURLOPT_FAILONERROR => false,
				),
			)
		);

		$response_body = json_decode( $token_response->getBody()->getContents() );

		if ( 200 === $token_response->getStatusCode() ) {
			self::$app_token = $response_body->access_token;
			return $response_body->access_token;
		} else {
			$error = isset( $response_body->error ) ? $response_body->error : $token_response->getStatusCode();
			throw new Exception( 'Token endpoint returned ' . $error, 100 );//phpcs:ignore
		}
	}

	/**
	 * Get working hour of user.
	 *
	 * @return array $response
	 */
	public static function get_working_hour() {

		$token = self::get_app_only_token();
		self::$app_client->setAccessToken( $token );

		$user        = isset( $_ENV['RC_MSGRAPH_USER'] ) ? $_ENV['RC_MSGRAPH_USER'] : 'admin'; //phpcs:ignore.
		$request_url = '/users/' . $user . '/mailboxSettings';
		try {
			$response = self::$app_client->createRequest( 'GET', $request_url )->setReturnType( Model\MailboxSettings::class )->execute();
			return $response->getWorkingHours();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}
	/**
	 * Get user calendar.
	 *
	 * @param string $start_date_time .
	 * @param string $end_date_time .
	 * @return array $response .
	 */
	public static function get_calender( $start_date_time, $end_date_time ) {

		$token = self::get_app_only_token();
		self::$app_client->setAccessToken( $token );

		$user        = isset( $_ENV['RC_MSGRAPH_USER'] ) ? $_ENV['RC_MSGRAPH_USER'] : 'admin'; //phpcs:ignore.
		$request_url = '/users/' . $user . '/calendarview?startdatetime=' . $start_date_time . '&enddatetime=' . $end_date_time;
		try {
			$response = self::$app_client->createRequest( 'GET', $request_url )->setReturnType( Model\Event::class )->execute();
			return $response;
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Create an outlook calendar event
	 *
	 * @param array  $user_data .
	 * @param string $event_data .
	 * @param string $mail_template .
	 *
	 * @return array $response
	 */
	public static function create_event( $user_data, $event_data, $mail_template = false ) {

		$token = self::get_app_only_token();
		self::$app_client->setAccessToken( $token );

		$start = array(
			'dateTime' => $event_data['start_date_time'],
			'timeZone' => 'UTC',
		);

		$end = array(
			'dateTime' => $event_data['end_date_time'],
			'timeZone' => 'UTC',
		);

		$attedees      = array(
			array(
				'emailAddress' => array(
					'name'    => $user_data['name'],
					'address' => $user_data['email'],
				),
			),
		);
		$subject  = isset( $_ENV['RC_MSGRAPH_ORG'] ) ? 'Meeting Scheduled with' . $_ENV['RC_MSGRAPH_ORG'] : 'Meeting Scheduled';// phpcs:ignore
		$mail_template = $mail_template ? $mail_template : 'We are pleased to inform you that your appointment with us has been confirmed. We look forward to the discussion.';
		$event         = new Model\Event();
		$event->setSubject( $subject )
		->setStart( $start )
		->setEnd( $end )
		->setAttendees( $attedees )
		->setBody(
			array(
				'contentType' => 'html',
				'content'     => $mail_template,
			)
		);

		$user        = isset( $_ENV['RC_MSGRAPH_USER'] ) ? $_ENV['RC_MSGRAPH_USER'] : 'admin'; //phpcs:ignore.
		$request_url = '/users/' . $user . '/events';
		try {
			$response = self::$app_client->createRequest( 'POST', $request_url )
						->attachBody( $event )
						->setReturnType( Model\Event::class )
						->execute();
			return array(
				'isSuccess'    => true,
				'meeting_link' => $response->getWeblink(),
			);
		} catch ( Exception $e ) {
			return array(
				'isSuccess' => true,
				'error'     => $e->getMessage(),
			);
		}
	}

	/**
	 * Create_mail
	 *
	 * @param array  $mail_data .
	 * @param string $mail_template .
	 *
	 * @return array response
	 */
	public static function create_mail( $mail_data, $mail_template ) {

		$token = self::get_app_only_token();
		self::$app_client->setAccessToken( $token );

		$mail_subject    = isset( $mail_data['subject'] ) && ! empty( $mail_data['subject'] ) ? $mail_data['subject'] : 'Hello';
		$mail_recipients = array(
			array(
				'emailAddress' => array(
					'address' => isset( $mail_data['email'] ) && ! empty( $mail_data['email'] ) ? $mail_data['email'] : $_ENV['RC_MSGRAPH_USER'],// phpcs:ignore
				),
			),
		);
		$mail_body       = array(
			'contentType' => 'html',
			'content'     => $mail_template,
		);

		$message = new Model\Message();
		$message->setSubject( $mail_subject );
		$message->setBody( $mail_body );
		$message->setToRecipients( $mail_recipients );

		$user        = isset( $_ENV['RC_MSGRAPH_USER'] ) ? $_ENV['RC_MSGRAPH_USER'] : 'admin'; //phpcs:ignore.
		$request_url = '/users/' . $user . '/sendMail';

		$request_url = '/users/' . $user . '/sendMail';
		try {
			$response = self::$app_client->createRequest( 'POST', $request_url )
						->attachBody( array( 'message' => $message ) )
						->setReturnType( Model\Event::class )
						->execute();
			return array(
				'isSuccess' => true,
				'response'  => $response,
			);
		} catch ( Exception $e ) {
			return array(
				'isSuccess' => false,
				'response'  => $e->getMessage(),
			);
		}
	}
}
