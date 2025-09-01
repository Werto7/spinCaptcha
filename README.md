<h1>spinCaptcha</h1>

<h2>1) Include and Instantiate</h2>

<pre><code class="language-php">&lt;?php
require_once 'spinCaptcha.php';

$captcha = new CaptchaBox();
?&gt;
</code></pre>

<hr />

<h2>2) Render the CAPTCHA Inside Your Form</h2>

<p>Place the widget where you want it to appear inside your <code>&lt;form&gt;...&lt;/form&gt;</code>:</p>

<pre><code class="language-php">&lt;form method="post" action=""&gt;
  &lt;div&gt;
    &lt;?php $captcha-&gt;showCaptchaInline(); ?&gt;
  &lt;/div&gt;

  &lt;button type="submit"&gt;Submit&lt;/button&gt;
&lt;/form&gt;
</code></pre>

<hr />

<h2>3) Verify the Submission</h2>

<p>After the form is submitted (e.g., on the same page or your handler), call:</p>

<pre><code class="language-php">&lt;?php
$result = $captcha-&gt;isVerified();
?&gt;
</code></pre>

<p><strong>Return values:</strong></p>
<ul>
  <li><code>true</code> — CAPTCHA solved correctly</li>
  <li><code>false</code> — CAPTCHA failed</li>
  <li><code>"timeout"</code> — User took too long; challenge expired</li>
</ul>

<hr />

<h2>Minimal End-to-End Example</h2>

<details>
  <summary>Show example</summary>

  ```php
<?php
require_once 'spinCaptcha.php';
$captcha = new CaptchaBox();

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $captcha->isVerified();
    if ($result === true) {
        $feedback = '✔️ Captcha verified successfully.';
    } elseif ($result === 'timeout') {
        $feedback = '⏱️ Captcha timed out. Please try again.';
    } else {
        $feedback = '❌ Captcha verification failed.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SpinCaptcha Demo</title>
</head>
<body>
  <h1>SpinCaptcha Demo</h1>

  <?php if (!empty($feedback)) : ?>
    <p><strong><?= htmlspecialchars($feedback, ENT_QUOTES) ?></strong></p>
  <?php endif; ?>

  <form method="post" action="">
    <div>
      <?php $captcha->showCaptchaInline(); ?>
    </div>

    <button type="submit">Submit</button>
  </form>
</body>
</html>
```
</details>

<hr />

<h2>Troubleshooting Tips</h2>
<ul>
  <li>Ensure <code>spinCaptcha.php</code> is readable and in your include path.</li>
  <li>Call <code>showCaptchaInline()</code> inside the <code>&lt;form&gt;</code> so the necessary fields are submitted.</li>
  <li>Always check <code>isVerified()</code> on POST to handle <code>true</code>, <code>false</code>, or <code>"timeout"</code> accordingly.</li>
</ul>


<h2>Captcha Generator</h2>

<p>The captcha generator generates round <code>.webp</code> images (300px by 300px), and stores them as Base64 strings inside text files.</p>

<ul>
  <li>The Base64 of the original image is saved in the <code>originals</code> folder.</li>
  <li>The Base64 of the randomly rotated image is saved in the <code>rotated</code> folder.</li>
</ul>

<h2>How to Generate Captcha Images</h2>

<p>
  The <code>spinCaptcha_pictures</code> folder does <strong>not</strong> need to be in the same directory as 
  <code>spinCaptcha.php</code>.
</p>

<p>
  You can set the path from which the images will be loaded, as long as you copy or move the 
  <code>spinCaptcha_pictures</code> folder into the corresponding location.
</p>

<p>
  Inside <code>spinCaptcha.php</code>, adjust the path in the constructor:
</p>

<pre><code class="language-php">$this-&gt;path = __DIR__;
</code></pre>

<p>
  Replace <code>__DIR__</code> with the desired absolute or relative path to your 
  <code>spinCaptcha_pictures</code> folder.
</p>

<ol>
  <li>Place source images into the <code>spinCaptcha_pictures</code> folder.</li>
  <li>Then run the script from the terminal:</li>
  <pre><code>php captcha-generator.php</code></pre>
</ol>

<p><strong>Warning:</strong> Any images placed in <code>spinCaptcha_pictures</code> will be <em>deleted</em> after the script runs!</p>

<h2>Repair Mode</h2>

<p>Sometimes, the Base64 text files and the entries in <code>answers.json</code> might not match. This can happen due to accidental deletion, system errors, or other issues.</p>

<p>You can repair the data by running:</p>

<pre><code>php captcha-generator.php repair</code></pre>

<p>This will:</p>

<ul>
  <li>Remove entries from <code>answers.json</code> that have no corresponding files.</li>
  <li>Delete Base64 files that don’t match any entries.</li>
</ul>

<p><strong>The generator is self-healing</strong> in repair mode, ensuring consistency between files and entries.</p>

<h2>Requirements</h2>

<ul>
  <li><code>imagemagick</code></li>
</ul>
