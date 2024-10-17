<?php
    $home_url = home_url();
    wp_enqueue_script('lottie-script');
    $lottie_src = get_stylesheet_directory_uri(). '/assets/lotties/check-mark.json';
    $user = wp_get_current_user();

?>

<div class="login-page">

    <div id="login-container">

        <div class="welcome-back">
            <lottie-player src="<?= $lottie_src ?>"  background="transparent"  speed="1"  style="width: 200px; height: 200px;"  loop autoplay></lottie-player>
            <h3> Welcome, <strong><?= $user->display_name ?></strong>, You are already logged in. You will be redirected to home dashboard. </h3>
            <p> If you are not redirecting, please click here </p>
            <a class="btn btn-primary" href="<?= $home_url ?>"> Go to Dashboard </a>
        </div>

    </div>

</div>

<script type="text/javascript">
    setTimeout(function() {
        window.location.href = "<?= $home_url ?>";
    }, 3000);
</script>