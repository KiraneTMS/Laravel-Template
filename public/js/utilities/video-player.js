document.addEventListener("DOMContentLoaded", function() {
    if (!document.getElementById('videoModal')) {
        const videoModalHTML = `
            <div class="custom-modal" id="videoModal">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <h5 class="custom-modal-title">View Video</h5>
                        <button type="button" class="custom-modal-close" onclick="hideModal('videoModal')">×</button>
                    </div>
                    <div class="custom-modal-body">
                        <div class="video-container">
                            <video id="customVideoPlayer" controls>
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <div class="custom-video-controls">
                            <button id="playPauseBtn" class="video-control-btn">
                                <i class="fas fa-play"></i>
                            </button>
                            <div class="video-progress-container">
                                <div class="video-progress-bar">
                                    <div id="videoProgress" class="video-progress"></div>
                                </div>
                                <span id="videoTime">0:00 / 0:00</span>
                            </div>
                            <button id="muteBtn" class="video-control-btn">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <button id="fullscreenBtn" class="video-control-btn">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="hideModal('videoModal')">Close</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', videoModalHTML);

        // Initialize custom video player
        initCustomVideoPlayer();
    }

    // Bind clickable video paths
    bindVideoPathEvents();
});

function initCustomVideoPlayer() {
    const video = document.getElementById('customVideoPlayer');
    const playPauseBtn = document.getElementById('playPauseBtn');
    const muteBtn = document.getElementById('muteBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const progressBar = document.getElementById('videoProgress');
    const timeDisplay = document.getElementById('videoTime');

    // Play/Pause functionality
    playPauseBtn.addEventListener('click', function() {
        if (video.paused) {
            video.play();
            playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
        } else {
            video.pause();
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        }
    });

    // Video events
    video.addEventListener('play', function() {
        playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
    });

    video.addEventListener('pause', function() {
        playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
    });

    video.addEventListener('ended', function() {
        playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
    });

    // Time update
    video.addEventListener('timeupdate', function() {
        // Update progress bar
        const percentage = (video.currentTime / video.duration) * 100;
        progressBar.style.width = percentage + '%';

        // Update time display
        const currentMinutes = Math.floor(video.currentTime / 60);
        const currentSeconds = Math.floor(video.currentTime % 60);
        const durationMinutes = Math.floor(video.duration / 60) || 0;
        const durationSeconds = Math.floor(video.duration % 60) || 0;

        timeDisplay.textContent = `${currentMinutes}:${currentSeconds.toString().padStart(2, '0')} / ${durationMinutes}:${durationSeconds.toString().padStart(2, '0')}`;
    });

    // Click on progress bar to seek
    const progressContainer = document.querySelector('.video-progress-bar');
    progressContainer.addEventListener('click', function(e) {
        const rect = progressContainer.getBoundingClientRect();
        const pos = (e.clientX - rect.left) / rect.width;
        video.currentTime = pos * video.duration;
    });

    // Mute/Unmute
    muteBtn.addEventListener('click', function() {
        video.muted = !video.muted;
        if (video.muted) {
            muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
        } else {
            muteBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        }
    });

    // Fullscreen
    fullscreenBtn.addEventListener('click', function() {
        if (video.requestFullscreen) {
            video.requestFullscreen();
        } else if (video.webkitRequestFullscreen) { /* Safari */
            video.webkitRequestFullscreen();
        } else if (video.msRequestFullscreen) { /* IE11 */
            video.msRequestFullscreen();
        }
    });

    // Reset player when modal is hidden
    document.getElementById('videoModal').addEventListener('click', function(event) {
        if (event.target === this) {
            resetVideoPlayer();
        }
    });

    document.querySelector('#videoModal .custom-modal-close').addEventListener('click', function() {
        resetVideoPlayer();
    });

    document.querySelector('#videoModal .btn-secondary').addEventListener('click', function() {
        resetVideoPlayer();
    });
}

function resetVideoPlayer() {
    const video = document.getElementById('customVideoPlayer');
    if (video) {
        video.pause();
        video.currentTime = 0;
        document.getElementById('playPauseBtn').innerHTML = '<i class="fas fa-play"></i>';
    }
}

function bindVideoPathEvents() {
    document.querySelectorAll('.video-path').forEach(element => {
        element.addEventListener('click', function(event) {
            event.stopPropagation();
            const videoPath = this.getAttribute('data-video-path');
            openVideoModal(videoPath);
        });
    });
}

function openVideoModal(videoPath) {
    if (videoPath) {
        const videoUrl = window.crudConfig.routes.storageUrl + videoPath;
        console.log('videoPath:', videoPath);
        console.log('videoUrl:', videoUrl);

        const customVideoPlayer = document.getElementById('customVideoPlayer');
        customVideoPlayer.src = videoUrl;
        customVideoPlayer.onerror = () => {
            console.error('Video load failed:', videoUrl);
            alert('Failed to load video: ' + videoUrl);
        };
        customVideoPlayer.onloadedmetadata = () => {
            console.log('Video loaded');
            showModal('videoModal');
        };

        // Reset player state
        resetVideoPlayer();
    } else {
        alert('No video available for this record.');
    }
}

// Function to check if a file is a video
function isVideoFile(filename) {
    const videoExtensions = ['.mp4', '.webm', '.ogg', '.mov', '.avi', '.wmv', '.flv', '.mkv'];
    const lowercaseFilename = filename.toLowerCase();
    return videoExtensions.some(ext => lowercaseFilename.endsWith(ext));
}

// Additional CSS for video player
const style = document.createElement('style');
style.textContent = `
    .video-container {
        position: relative;
        width: 100%;
        background-color: #000;
        margin-bottom: 10px;
    }

    #customVideoPlayer {
        width: 100%;
        max-height: 70vh;
        background-color: #000;
    }

    .custom-video-controls {
        display: flex;
        align-items: center;
        background-color: #f8f9fa;
        padding: 8px;
        border-radius: 4px;
    }

    .video-control-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px 10px;
        color: #495057;
    }

    .video-control-btn:hover {
        color: #007bff;
    }

    .video-progress-container {
        flex-grow: 1;
        display: flex;
        align-items: center;
        margin: 0 10px;
    }

    .video-progress-bar {
        height: 6px;
        background-color: #dee2e6;
        border-radius: 3px;
        cursor: pointer;
        position: relative;
        flex-grow: 1;
        margin-right: 10px;
    }

    .video-progress {
        height: 100%;
        background-color: #007bff;
        border-radius: 3px;
        width: 0%;
    }

    #videoTime {
        font-size: 0.8rem;
        min-width: 80px;
        text-align: right;
    }
`;
document.head.appendChild(style);
