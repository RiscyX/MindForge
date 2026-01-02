<?php
/**
 * @var \App\View\AppView $this
 * @var string $resetUrl
 */
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body bgcolor="#010104" style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#010104">
		<tr>
			<td align="center" style="padding: 40px 20px;">
				<!-- Main container - fixed width for better compatibility -->
				<table width="520" border="0" cellspacing="0" cellpadding="0" bgcolor="#0a0a0f" style="border: 1px solid #2a2a35;">
					<tr>
						<td style="padding: 36px;">
							<!-- Logo/Brand -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="padding-bottom: 24px;">
										<h1 style="margin: 0; padding: 0; font-size: 24px; font-weight: bold; color: #eae9fc; font-family: Arial, sans-serif;">
											MindForge
										</h1>
									</td>
								</tr>
							</table>

							<!-- Heading -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="padding-bottom: 16px;">
										<h2 style="margin: 0; padding: 0; font-size: 28px; font-weight: bold; color: #eae9fc; font-family: Arial, sans-serif;">
											<?= __('Reset your password') ?>
										</h2>
									</td>
								</tr>
							</table>

							<!-- Message -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="padding-bottom: 28px; font-size: 16px; line-height: 24px; color: #c7c6db; font-family: Arial, sans-serif;">
										<?= __('You requested a password reset for your MindForge account. Click the button below to set a new password:') ?>
									</td>
								</tr>
							</table>

							<!-- CTA Button -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="padding-bottom: 28px;">
										<table border="0" cellspacing="0" cellpadding="0">
											<tr>
												<td bgcolor="#575ddb" style="padding: 14px 32px;">
													<a href="<?= h($resetUrl) ?>"
													   style="font-size: 16px; font-weight: bold; color: #ffffff; font-family: Arial, sans-serif; text-decoration: none;">
														<?= __('Reset Password') ?>
													</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>

							<!-- Fallback Link -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="padding-bottom: 8px; font-size: 14px; color: #999aab; font-family: Arial, sans-serif;">
										<?= __('Or copy and paste this link into your browser:') ?>
									</td>
								</tr>
								<tr>
									<td style="padding-bottom: 28px; font-size: 14px; font-family: Arial, sans-serif;">
										<a href="<?= h($resetUrl) ?>" style="color: #575ddb;"><?= h($resetUrl) ?></a>
									</td>
								</tr>
							</table>

							<!-- Security Notice -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="font-size: 14px; color: #999aab; font-family: Arial, sans-serif;">
										<?= __('If you did not request this, you can safely ignore this email.') ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
