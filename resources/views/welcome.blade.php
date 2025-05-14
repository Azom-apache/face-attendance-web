<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Face Descriptor Capture</title>
  <!-- Load face-api.js WITHOUT defer -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.2.1/dist/jquery.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="></script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <style>
    body { font-family: Arial, sans-serif; text-align: center; margin-top: 20px; }
    video { border: 1px solid #ccc; }
    button { margin-top: 10px; padding: 10px 20px; font-size: 16px; }
  </style>
</head>
<body>
  <h2>Face Detection Attendance - Capture Face Descriptor</h2>

  <video id="video" width="720" height="560" autoplay muted></video><br />
  <button id="captureBtn">Capture Face & Submit</button>

  <script>
    // Wait for the DOM to load
    document.addEventListener('DOMContentLoaded', async () => {
      const video = document.getElementById('video');
      const captureBtn = document.getElementById('captureBtn');

      // Load face-api.js models from /models folder on your server
      async function loadModels() {
        try {
          await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
          await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
          await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
          console.log("Models loaded");
        } catch (err) {
          alert('Failed to load face-api models: ' + err);
          throw err;
        }
      }

      // Start webcam video
      async function startVideo() {
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
          video.srcObject = stream;
        } catch (err) {
          alert("Error accessing webcam: " + err);
          console.error(err);
        }
      }

      // Detect face and get descriptor
      async function captureFaceDescriptor() {
        try {
          const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                          .withFaceLandmarks()
                                          .withFaceDescriptor();
          if (!detection) {
            alert("No face detected, please try again.");
            return null;
          }
          return detection.descriptor;
        } catch (err) {
          alert('Error detecting face: ' + err);
          console.error(err);
          return null;
        }
      }

      // Load models and start video
      await loadModels();
      await startVideo();

      // Button click event
      captureBtn.addEventListener('click', async () => {
        captureBtn.disabled = true;
        captureBtn.textContent = 'Processing...';

        const descriptor = await captureFaceDescriptor();

        if (!descriptor) {
          captureBtn.disabled = false;
          captureBtn.textContent = 'Capture Face & Submit';
          return;
        }

        // Convert Float32Array to normal array
        const descriptorArray = Array.from(descriptor);

        const payload = {
          name: 'John Doe',
          email: 'john@example.com',
          password: 'password123',
          password_confirmation: 'password123',
          face_descriptor: descriptorArray
        };

        try {
          const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              // Remove CSRF header if not using Laravel Blade or exempt route in backend
            },
            body: JSON.stringify(payload)
          });

          const data = await response.json();

          if (response.ok) {
            alert(data.message || 'User registered successfully');
          } else {
            alert('Error: ' + (data.message || JSON.stringify(data)));
          }
        } catch (err) {
          alert('Failed to submit data: ' + err.message);
          console.error(err);
        }

        captureBtn.disabled = false;
        captureBtn.textContent = 'Capture Face & Submit';
      });
    });
  </script>
</body>
</html>
