<div class="login-page">

    <div id="login-container" class="right-panel-active">
        <div class="form-container sign-up-container">

            <?php echo do_shortcode('[student_registration_form]'); ?>
        </div>

        <div class="form-container sign-in-container">
            <?php echo do_shortcode('[custom_login_form]'); ?>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome to <br> Noor World!</h1>
                    <p> If you already a member, please sign in here. </p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>


</div>
