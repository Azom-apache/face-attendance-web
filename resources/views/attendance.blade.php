<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Face Attendance Capture</title>
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
  <div id="message" style="margin-top:15px; color:red;"></div>

  <script>
    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const messageEl = document.getElementById('message');

    async function loadModels() {
      await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
      await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
      await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
      console.log("Models loaded");
    }

    async function startVideo() {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
      } catch (err) {
        alert("Error accessing webcam: " + err.message);
      }
    }

    async function captureFaceDescriptor() {
      const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 });
      const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();

      if (!detection) {
        alert("No face detected. Please try again.");
        return null;
      }

      return detection.descriptor;
    }

    captureBtn.addEventListener('click', async () => {
      captureBtn.disabled = true;
      captureBtn.textContent = 'Processing...';
      messageEl.textContent = '';

      const descriptor = await captureFaceDescriptor();
      if (!descriptor) {
        captureBtn.disabled = false;
        captureBtn.textContent = 'Capture Face & Submit';
        return;
      }

      const descriptorArray = Array.from(descriptor);
      try {
        const response = await fetch('http://localhost:8000/api/mark-attendance', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ face_descriptor: descriptorArray })
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || 'Error occurred');
        }

        const data = await response.json();
        alert(data.message);
      } catch (error) {
        messageEl.textContent = 'Error: ' + error.message;
      } finally {
        captureBtn.disabled = false;
        captureBtn.textContent = 'Capture Face & Submit';
      }
    });

    (async () => {
      await loadModels();
      await startVideo();
    })();
  </script>
</body>
</html>
