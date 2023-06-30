
<?=sprintf(lang('IonAuth.emailForgotPassword_heading'), $identity)?> 

<?=lang('IonAuth.emailForgotPassword_subheading');?> <?=base_url('auth/reset_password/' . $forgottenPasswordCode)?>

