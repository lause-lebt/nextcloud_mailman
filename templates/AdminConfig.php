<?php

/** @var $l \OCP\IL10N */
/** @var $_ array */

#script("mailman", "config");
style("mailman", "admin");

?>

<div id="mailman-admincfg-server" class="section">
	<h2> <?php p($l->t("Mailman3 server")); ?></h2>
	<p>
	<input id="mailman-admincfg-url" name="mailman-admincfg-url" type="text" placeholder="<?php p($l->t('REST URL')); ?>" value="<?php p($_['url']); ?>">
		<label for="mailman-admincfg-url"><?php p($l->t("REST URL")); ?></label>
	</p>
	<p>
	<input id="mailman-admincfg-cred" name="mailman-admincfg-cred" type="text" placeholder="<?php p($l->t('REST credentials')); ?>" value="<?php p($_['cred']); ?>">
		<label for="mailman-admincfg-cred"><?php p($l->t("REST Credentials")); ?></label>
	</p>
	<p>
	<input id="mailman-admincfg-domain" name="mailman-admincfg-domain" type="text" placeholder="<?php p($l->t('lists domain')); ?>" value="<?php p($_['domain']); ?>">
		<label for="mailman-admincfg-domain"><?php p($l->t("Lists domain")); ?></label>
	</p>
	<p>
	<input id="mailman-admincfg-limit" name="mailman-admincfg-limit" type="text" placeholder="<?php p($l->t('mail size limit')); ?>" value="<?php p($_['limit']); ?>">
		<label for="mailman-admincfg-limit"><?php p($l->t("Mail size limit (kB)")); ?></label>
	</p>
</div>

<div id="mailman-admincfg-lists" class="section">
	<h2> <?php p($l->t("Mailing lists")); ?></h2>
	<p>
		<pre><?php print_r($_['mmlists']); ?></pre>
		<pre><?php print_r($_['lists']); ?></pre>
	</p>
</div>
