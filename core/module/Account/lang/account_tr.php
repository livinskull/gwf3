<?php
$lang = array(
# Başlıkları
'form_title' => 'Hesap ayarları',
'chmail_title' => 'yeni e-posta girin',
# Başlıkları
'th_username' => 'Kullanıcı adınız',
'th_email' => 'İletişim E-posta',
'th_demo' => 'Demografik Seçenekler – Sen %1$s bu yalnızca bir kez içinde değiştirebilirsiniz.',
'th_countryid' => 'Ülke',
'th_langid' => 'Ana dilinizde',
'th_langid2' => 'Ortaöğretim Dil',
'th_birthdate' => 'Kişisel Doğum Günü',
'th_gender' => 'Kişisel Cinsiyet',
'th_flags' => 'Seçenekler - Sen sinek da, bu geçiş yapabilirsiniz',
'th_adult' => 'Eğer yetişkin içerik görmek istiyor musunuz?',
'th_online' => 'Çevrimiçi olduğunuzu gizleyin isminde?',
'th_show_email' => 'gösterin EMail halka?',
'th_avatar' => 'Avatarınız',
'th_approvemail' => '<b>EMail Onaylı değil</b>',
'th_email_new' => 'Yeni E-posta',
'th_email_re' => 'Re-Type E-posta',

# Düğmeler
'btn_submit' => 'Değişiklikleri Kaydet',
'btn_approvemail' => 'Onayla EMail',
'btn_changemail' => 'Set Yeni E-posta',
'btn_drop_avatar' => 'Sil Avatar',

# Hataları
'err_token' => 'Geçersiz simge.',
'err_email_retype' => 'E-postalarınız doğru bir şekilde yeniden türü var.',
'err_delete_avatar' => 'Senin Avatar silmek bir hata oluştu.',
'err_no_mail_to_approve' => 'Bir e-posta onaylaması belirledik.',
'err_already_approved' => 'E-posta adresiniz zaten onaylanmıştır.',
'err_no_image' => 'Kişisel yüklenen dosya bir görüntü, ya da çok küçük değil.',
'err_demo_wait' => 'Siz yakın zamanda demografik seçenekleri değiştirilemez. Lütfen %1$s bekleyin.',
'err_birthdate' => 'Kişisel doğum geçersiz görünüyor.',

# Mesajlar
'msg_mail_changed' => 'Kişisel e-posta iletişim <b>%1$s olarak değiştirildi </b>.',
'msg_deleted_avatar' => 'Kişisel avatar resim silindi.',
'msg_avatar_saved' => 'Yeni Avatar görüntü kaydedildi.',
'msg_demo_changed' => 'Kişisel demografik seçenekleri değiştirildi.',
'msg_mail_sent' => 'Biz size bir e-posta değişiklikleri gerçekleştirmek için gönderdik. Lütfen yönergeleri izleyin.',
'msg_show_email_on' => 'Kişisel EMail şimdi halka gösterilir.',
'msg_show_email_off' => 'Kişisel EMail şimdi halktan gizlenir.',
'msg_adult_on' => 'Hesabınız artık yetişkin içerik = görebilirsiniz.',
'msg_adult_off' => 'Yetişkin içerik şimdi sizin için gizlidir.',
'msg_online_on' => 'Kişisel çevrimiçi durumu şimdi gizlidir.',
'msg_online_off' => 'Kişisel çevrimiçi durumu şimdi görünür.',

# Admin Config
'cfg_avatar_max_x' => 'Avatar Maksimum genişlik',
'cfg_avatar_max_y' => 'Avatar Maksimum yükseklik',
'cfg_avatar_min_x' => 'Avatar Minimum Genişlik',
'cfg_avatar_min_y' => 'Avatar Minimum Yükseklik',
'cfg_adult_age' => 'Yaş sınırı için Yetişkin İçerik',
'cfg_demo_changetime' => 'Demografik değişim Timeout',
'cfg_mail_sender' => 'Hesap Change Email Sender',
'cfg_show_adult' => 'Site yetişkin içerik var?',
'cfg_show_gender' => 'göster Cinsiyet seçin?',
'cfg_use_email' => 'iste EMail Hesabı değişiklikleri yapmalı?',
'cfg_show_avatar' => 'göster Avatar Yükleme?',

############################
# --- EMAIL --- #
# DEĞİŞİM POSTA A
#BURAYA ALINMIŞTIR 
'chmaila_subj' => GWF_SITENAME. ': Değiştirin EMail',
'chmaila_body' =>
	'Sevgili %1$s'. PHP_EOL.
	PHP_EOL.
	'Sen '.GWF_SITENAME.' e-postanıza değiştirmek istedi.'.PHP_EOL.
	'Bunu yapmak için, ziyaret etmek için aşağıdaki adresi.'. PHP_EOL.
	'Eğer, e-posta değişikliği talep etmedim, bu mail görmezden gelebilirsiniz ya da bu konuda bize uyarı.'.PHP_EOL.
	PHP_EOL.
	'%2$s'. PHP_EOL.
	PHP_EOL.
	'Saygılarımızla'.PHP_EOL.
	''. GWF_SITENAME. ' personel',

# DEĞİŞİM MAIL B
'chmailb_subj' => GWF_SITENAME. ': Onaylayın EMail',
'chmailb_body' =>
	'Sevgili %1$s'. PHP_EOL.
	PHP_EOL.
	'Eğer aşağıdaki adresi ziyaret ederek onaylamak zorunda ana ulaşım adresi:'. PHP_EOL.
	'Bu e-posta adresini kullanın.'.
	'%2$s'. PHP_EOL.
	PHP_EOL.
	'Saygılarımızla'.PHP_EOL.
	GWF_SITENAME.' personel',

# DEĞİŞİM DEMO
'chdemo_subj' => GWF_SITENAME. ': Değişim Demografik Ayarlar',
'chdemo_body' =>
	'Sevgili %1$s'. PHP_EOL.
	PHP_EOL.
	'You kurulum talep ettiniz veya demografik ayarlarını değiştirmek.'. PHP_EOL.
	'You %2$s sadece bir kez içinde, bu nedenle bilgilerin doğru önce devam olduğundan emin olun bunu yapabilirsiniz.'.PHP_EOL.
	PHP_EOL.
	'Cinsiyet: %3$s'.PHP_EOL.
	'Ülke: %4$s'.PHP_EOL.
	'Birincil Dil: %5$s'. PHP_EOL.
	'Ortaöğretim Dil: %6$s'. PHP_EOL.
	'Doğum Günü: %7$s'. PHP_EOL.
	PHP_EOL.
	'Bu ayarları tutmak için, aşağıdaki bağlantıyı:'. PHP_EOL. ### ??? kullanmak istiyorsanız lütfen. ???
	'%8$s'.
	PHP_EOL.
	'Saygılarımızla'. PHP_EOL.
	GWF_SITENAME. ' personel ',

# Yeni Bayraklar
'th_allow_email' => 'İzin kişi EMail size',
'msg_allow_email_on' => 'İnsanlar artık e-posta adresinizi bozulmadan e-posta gönderebilirsiniz.',
'msg_allow_email_off' => 'EMail temas kapatılır.',

'th_show_bday' => 'göster senin doğum',
'msg_show_bday_on' => 'Doğum gününüz şimdi bu özelliği gibi üyelerine duyurulur.',
'msg_show_bday_off' => 'Doğum gününüz artık duyurdu değildir.',

'th_show_obday' => 'göster diğer doğum günü',
'msg_show_obday_on' => 'Artık diğer halklar doğum günlerini görürsünüz.',
'msg_show_obday_off' => 'Sen doğum günü görmezlikten şimdi duyurdu.',

# V2.02 Hesap Silme
'pt_accrm' => 'Sil Hesap',
'mt_accrm' => 'Sil hesabınızda üzerine'. GWF_SITENAME,
'pi_accrm' =>
	'Size üzerine '.GWF_SITENAME.' hesabınızı silmek ister gibi görünüyor.<br/>'.
	'Biz, bu da silmiş olmayacaktır duymak üzücü olan, sadece özürlü. <br/>'.
	'Bu kullanıcı adı, profiller, vs Tüm bağlantıları, kullanışsız olacak ya da konuk olarak değiştirildi. Bu geri döndürülemez.<br/>'.
	'Hesabınızı devre dışı devam etmeden önce, bize Silmek için sebep (ler).<br/>', # ??? Ile ilgili bir not
'th_accrm_note' => 'Not',
'btn_accrm' => 'Sil Hesap',
'msg_accrm' => 'Hesabınız olarak silinir ve tüm başvuruları silinmiş var gerektiğini işaretlenmiş var. Sen oturum var.<br/>',
'ms_accrm' => GWF_SITENAME. ': %1$s hesap silinmesi',
'mb_accrm' =>
	'Sevgili Çalışanlar'. PHP_EOL.
	PHP_EOL.
	'Kullanıcı %1$s sadece onun silmiş olabilir ve bu notu:'. PHP_EOL.PHP_EOL.
	'%2$s',

	
	# v2.03 Email Options
	'th_email_fmt' => 'Preferred EMail Format',
	'email_fmt_text' => 'Plain Text',
	'email_fmt_html' => 'Simple HTML',
	'err_email_fmt' => 'Please select a valid EMail Format.',
	'msg_email_fmt_0' => 'You will now receive emails in simple html format.',
	'msg_email_fmt_4096' => 'You will now receive emails in plain text format.',
	'ft_gpg' => 'Setup PGP/GPG Encryption',
	'th_gpg_key' => 'Upload your public key',
	'th_gpg_key2' => 'Or paste it here',
	'tt_gpg_key' => 'When you have set a pgp key all the emails sent to you by the scripts are encrypted with your public key',
	'tt_gpg_key2' => 'Either paste your public key here, or upload your public key file.',
	'btn_setup_gpg' => 'Upload Key',
	'btn_remove_gpg' => 'Remove Key',
	'err_gpg_setup' => 'Either upload a file which contains your public key or paste your public key in the text area.',
	'err_gpg_key' => 'Your public key seems invalid.',
	'err_gpg_token' => 'Your gpg fingerprint token does not match our records.',
	'err_no_gpg_key' => 'The user %1$s did not submit a public key yet.',
	'err_no_mail' => 'You don`t have an approved main contact email address.',
	'err_gpg_del' => 'You don`t have a validated GPG key to delete.',
	'err_gpg_fine' => 'You already have a GPG key. Please delete it first.',
	'msg_gpg_del' => 'Your GPG key has been deleted successfully.',
	'msg_setup_gpg' => 'Your GPG has been stored and is in use now.',
	'mails_gpg' => GWF_SITENAME.': Setup GPG Encryption',
	'mailb_gpg' =>
		'Dear %1$s,'.PHP_EOL.
		PHP_EOL.
		'You have decided to turn on gpg encryption for emails sent by this robot.'.PHP_EOL.
		'To do so, follow the link below:'.PHP_EOL.
		PHP_EOL.
		'%2$s'.PHP_EOL.
		PHP_EOL.
		'Kind Regards'.PHP_EOL.
		'The '.GWF_SITENAME.' staff',
		
	# v2.04 Change Password
	'th_change_pw' => '<a href="%1$s">Change your password</a>',
		
	'err_gpg_raw' => GWF_SITENAME.' does only support ascii armor format for your public GPG key.',
	# v2.05 (fixes)
	'btn_delete' => 'Delete Account',
	'err_email_invalid' => 'Your email looks invalid.',
	# v3.00 (fixes3)
	'err_email_taken' => 'This email address is already in use.',
);

?>