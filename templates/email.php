<?php
/**
 * Email template
 *
 * @package RawConscious
 */

/**
 * Undocumented function
 *
 * @param array $variables .
 * @return string output buffer
 */
function rc_msgraph_get_email_template( $variables = null ) {
	$name         = $variables['name'];
	$email        = $variables['email'];
	$date         = $variables['date'];
	$time         = $variables['time'];
	$time_zone    = $variables['time_zone'];
	$meeting_link = $variables['meeting_link'];

	ob_start();
	?>

	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Appointment Confirmation</title>
	</head>
	<body>
		<table style="margin: 0 auto; max-width: 600px;" cellpadding="0" cellspacing="0">
			<tr>
				<td style="text-align: center; background-color: #007BFF; padding: 20px;">
					<h1 style="color: #ffffff;">Appointment Confirmation</h1>
				</td>
			</tr>
			<tr>
				<td style="background-color: #ffffff; padding: 20px;">
					<p>Dear <?php echo esc_html( $name ); ?>,</p>
					<p>We are pleased to confirm your appointment with us:</p>
					<p><strong>Appointment Date & Time:</strong> <?php echo esc_html( $date ); ?> at <?php echo esc_html( $time ); ?> </p>
					<p><strong>Timezone:</strong> <?php echo esc_html( $time_zone ); ?> </p>
					<p>If you need to reschedule or cancel your appointment, please contact us as soon as possible.</p>
					<p>Thank you for choosing us. We look forward to serving you.</p>
					<p>Best regards,</p>
					<p>RawConscious</p>
					<p><a href="<?php echo esc_attr_e( $meeting_link );//phpcs:ignore ?>" style="color: #007BFF; text-decoration: none;">View Appointment Details</a></p>
				</td>
			</tr>
		</table>
	</body>
	</html>

	<?php
	return ob_get_clean();
}
