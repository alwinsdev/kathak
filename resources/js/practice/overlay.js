/**
 * overlay.js — pure presentation: draw prediction bounding boxes over the feed.
 * No camera, transport, or AI logic.
 */
export function drawPredictions(canvas, video, predictions = []) {
    if (!canvas || !video) {
        return;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    predictions.forEach((p) => {
        if (p.x == null || p.width == null) {
            return; // classification-only prediction, nothing to draw
        }

        const x = p.x - p.width / 2;
        const y = p.y - p.height / 2;

        ctx.strokeStyle = '#14b8a6';
        ctx.lineWidth = 3;
        ctx.strokeRect(x, y, p.width, p.height);

        const label = `${p.class} ${(p.confidence * 100).toFixed(0)}%`;
        ctx.font = '18px sans-serif';
        const labelWidth = ctx.measureText(label).width + 10;

        ctx.fillStyle = '#14b8a6';
        ctx.fillRect(x, y - 24, labelWidth, 24);
        ctx.fillStyle = '#ffffff';
        ctx.fillText(label, x + 5, y - 7);
    });
}

export function clearOverlay(canvas) {
    if (!canvas) {
        return;
    }
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}
