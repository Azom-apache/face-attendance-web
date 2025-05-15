<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Face Descriptor Capture</title>
  <script src="{{asset('face-api.min.js')}}"></script>
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
    document.addEventListener('DOMContentLoaded', async () => {
      const video = document.getElementById('video');
      const captureBtn = document.getElementById('captureBtn');

      // Load face-api.js models
      async function loadModels() {
        try {
          await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
          await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
          await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
          console.log("Models loaded successfully");
        } catch (err) {
          alert('Failed to load face-api models: ' + err);
          throw err;
        }
      }

      // Start webcam stream
      async function startVideo() {
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
          video.srcObject = stream;
        } catch (err) {
          alert("Error accessing webcam: " + err.message);
        }
      }

      // Detect face and extract descriptor
      async function captureFaceDescriptor() {
        try {
          const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 });

          const detection = await faceapi.detectSingleFace(video, options)
                                          .withFaceLandmarks()
                                          .withFaceDescriptor();

          console.log('Detection result:', detection);

          if (!detection) {
            alert("No face detected, please try again.");
            return null;
          }

          return detection.descriptor;
        } catch (err) {
          alert('Face detection failed: ' + err.message);
          return null;
        }
      }

      // Load models and start webcam
      await loadModels();
      await startVideo();

      // On button click: capture and send face descriptor
      captureBtn.addEventListener('click', async () => {
        captureBtn.disabled = true;
        captureBtn.textContent = 'Processing...';

        const descriptor = await captureFaceDescriptor();

        if (!descriptor) {
          captureBtn.disabled = false;
          captureBtn.textContent = 'Capture Face & Submit';
          return;
        }

        const descriptorArray = Array.from(descriptor);

        const payload = {
          name: 'Azom',
          email: 'azom@example.com',
          password: 'password123',
          password_confirmation: 'password123',
          face_descriptor: descriptorArray
        };

        try {
          const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
          });

          const data = await response.json();

          if (response.ok) {
            alert(data.message || 'User registered successfully.');
          } else {
            alert('Error: ' + (data.message || JSON.stringify(data)));
          }
        } catch (err) {
          alert('Failed to submit data: ' + err.message);
        }

        captureBtn.disabled = false;
        captureBtn.textContent = 'Capture Face & Submit';
      });
    });
  </script>
</body>
</html>
