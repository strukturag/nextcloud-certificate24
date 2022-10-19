<?php

script('esig', 'esig-main');
?>

<script nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" id="vinegar-server">
const vinegar_server = '<?php p($_['vinegar-server']); ?>';
</script>
