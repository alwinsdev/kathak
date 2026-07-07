/**
 * overlay.js — pure presentation: draw prediction bounding boxes over the feed.
 * No camera, transport, or AI logic.
 */
export function drawPredictions(canvas, video, predictions = [], targetLabel = '', targetName = '') {
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
        const w = p.width;
        const h = p.height;

        const isMatch = targetLabel && p.class.toLowerCase() === targetLabel.toLowerCase();
        
        // Premium color palette: Emerald/Teal for match, Rose/Red for mismatch
        const primaryColor = isMatch ? '#14b8a6' : '#f43f5e'; 
        const fillColor = isMatch ? 'rgba(20, 184, 166, 0.08)' : 'rgba(244, 63, 94, 0.08)';
        const borderColor = isMatch ? 'rgba(20, 184, 166, 0.4)' : 'rgba(244, 63, 94, 0.4)';

        // 1. Draw semi-transparent filled box
        ctx.fillStyle = fillColor;
        ctx.fillRect(x, y, w, h);

        // 2. Draw thin border
        ctx.strokeStyle = borderColor;
        ctx.lineWidth = 1.5;
        ctx.strokeRect(x, y, w, h);

        // 3. Draw premium thick corner brackets
        ctx.strokeStyle = primaryColor;
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        const len = Math.min(24, w * 0.2, h * 0.2); // corner line length

        // Top-Left corner
        ctx.beginPath();
        ctx.moveTo(x + len, y);
        ctx.lineTo(x, y);
        ctx.lineTo(x, y + len);
        ctx.stroke();

        // Top-Right corner
        ctx.beginPath();
        ctx.moveTo(x + w - len, y);
        ctx.lineTo(x + w, y);
        ctx.lineTo(x + w, y + len);
        ctx.stroke();

        // Bottom-Left corner
        ctx.beginPath();
        ctx.moveTo(x, y + h - len);
        ctx.lineTo(x, y + h);
        ctx.lineTo(x + len, y + h);
        ctx.stroke();

        // Bottom-Right corner
        ctx.beginPath();
        ctx.moveTo(x + w - len, y + h);
        ctx.lineTo(x + w, y + h);
        ctx.lineTo(x + w, y + h - len);
        ctx.stroke();

        // 4. Draw Label pill above the box
        const labelText = isMatch 
            ? `${targetName} ${(p.confidence * 100).toFixed(0)}%`
            : `Incorrect Mudra ${(p.confidence * 100).toFixed(0)}%`;

        ctx.font = 'bold 13px system-ui, -apple-system, sans-serif';
        const textMetrics = ctx.measureText(labelText);
        const labelW = textMetrics.width + 16;
        const labelH = 26;
        const labelX = x;
        const labelY = y - labelH - 6;

        // Draw rounded pill background for the label
        ctx.fillStyle = primaryColor;
        ctx.beginPath();
        if (typeof ctx.roundRect === 'function') {
            ctx.roundRect(labelX, labelY, labelW, labelH, 6);
        } else {
            ctx.rect(labelX, labelY, labelW, labelH);
        }
        ctx.fill();

        // Draw label text
        ctx.fillStyle = '#ffffff';
        ctx.textBaseline = 'middle';
        ctx.fillText(labelText, labelX + 8, labelY + labelH / 2);
    });
}

export function clearOverlay(canvas) {
    if (!canvas) {
        return;
    }
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}
