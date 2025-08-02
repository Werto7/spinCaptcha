<h1>spinCaptcha</h1>

<p><strong>Status:</strong> The captcha is currently <em>not working</em>. It used to work until a small change was made in the code, which was not expected to affect it. When I have time, I’ll look into where the problem is.</p>

<h2>Captcha Generator</h2>

<p>The captcha generator <strong>does work</strong>. It generates round <code>.webp</code> images (300px by 300px), and stores them as Base64 strings inside text files.</p>

<ul>
  <li>The Base64 of the original image is saved in the <code>originals</code> folder.</li>
  <li>The Base64 of the randomly rotated image is saved in the <code>rotated</code> folder.</li>
</ul>

<h2>How to Generate Captcha Images</h2>

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
