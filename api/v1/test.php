Test!
<p>
<?php if (auth_user()) { ?>
You have access because you are logged-in to the website as <em><?=auth_username()?></em>
<?php } else { ?>
When you see this, you have been successfully authenticated using HTTP Digest or OAuth
<?php } ?>
