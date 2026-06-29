# V2 AI Architecture Review — Siddha Mudra Recognition
**Project:** Siddha Mudra Therapy · **Branch:** `feature/yolov11-ai` · **Date:** 2026-06-29
**Authored as:** Senior AI Architect / CV Engineer / ML Engineer / Healthcare‑AI Solution Architect
**Status:** Evidence-based blueprint (no code). **Supersedes the *engine choice* in `V2-YOLOV11-DESIGN.md`** (that document is retained as "Option 1, evaluated").

> **Bias check up front:** I was previously asked to design a YOLO solution. This review deliberately re-opens that decision and tests it against alternatives using CV first principles and *your application's own measured behaviour*. The conclusion is **not** YOLO-as-primary.

---

## 1. Executive Summary
The therapeutic goal is to verify that a patient's **hand is in a specific configuration** (thumb here, this finger curled, these fingers touching, palm at this orientation) and to **coach them toward it in real time**. That is a **fine-grained articulated-hand-pose problem**, not an object-detection problem.

A bounding-box detector (YOLO) answers *"what object, where"*. But in this app there is always exactly one obvious object — a hand — filling the frame; **localization is trivial and not the challenge.** The challenge lives *inside* the box: sub-pixel-scale finger geometry. Your v1 Roboflow detector demonstrated the failure mode precisely — it classified a fist as `shikhar` and a spread hand as `shuktund` **regardless of the intended mudra**, because two mudras can share a near-identical bounding box and the detector latched onto coarse appearance/background instead of finger configuration.

**Recommendation:** a **landmark-based two-stage pipeline** — **MediaPipe Hands (21 3D hand landmarks) → normalized geometric features → a lightweight classifier**, with an **explainability comparator** against per-mudra canonical templates. Served by FastAPI behind your existing `InferenceClient`. This is Option 2 (productized as Option 6). It is **geometry-based, so it is invariant to skin tone, lighting and background** (the exact nuisances that broke v1), needs **far less data**, runs **real-time on CPU**, is **inherently explainable** (it can say *"move your thumb closer to your index finger"*), and is **mobile/edge/offline-ready** for free. **YOLO is not required** for the single-hand therapy use case (optional future role only).

The Laravel application is **unchanged** except: the new provider behind `InferenceClient`, and **one additive, backward-compatible field** to carry explainable feedback (justified by your explicit Explainable-AI requirement).

## 2. Problem Analysis — what kind of CV problem is this?
| Candidate framing | Verdict | Reasoning |
|---|---|---|
| Object Detection | ✗ primary | There is one obvious hand filling the frame; *where* is trivial. Detection's strength (localization among clutter/scale) is irrelevant; its weakness (coarse in-box appearance) is exactly where mudras differ |
| Image Classification (whole-frame CNN) | ✗ | Conflates hand config with background/lighting/skin → context overfitting (**v1's measured failure**) |
| Body Pose Estimation | ✗ | The discriminative signal is the *hand*, not the body |
| **Hand Landmark Detection** | ✅ core primitive | The 21 keypoints' *relative geometry* literally *defines* a mudra (thumb position, curl, spacing, contact, orientation) |
| Gesture Recognition | ✅ framing | Static mudra = single-frame gesture classification on landmarks; "hold" = temporal stability of that class |
| **Multi-stage Recognition** | ✅ productized form | Stage 1 landmarks → Stage 2 classify → Stage 3 explain. The natural decomposition |

**Conclusion:** this is **fine-grained static hand-gesture recognition**, best solved as **hand-landmark detection → geometric classification → explainable comparison** (a two-stage/multi-stage pipeline).

## 3. AI Approach Comparison
Scored for *this* problem (Siddha mudra recognition + coaching), not in general. Suitability is the bottom line.

### Option 1 — YOLOv11 Object Detection
- **Accuracy (fine mudras):** Low–Medium. The classifier head must separate near-identical boxes using in-box pixels under heavy nuisance variation → confuses fist/finger variants (v1 evidence).
- **Latency:** Excellent (5–12 ms GPU) — but speed doesn't fix the accuracy framing.
- **Complexity:** Medium (training + GPU serving).
- **Hardware:** Wants a GPU for real-time.
- **Training difficulty:** High *data* burden — needs hundreds of *diverse* bbox-labeled images **per class** just to fight context overfitting.
- **Deployment / Maintenance:** Standard but GPU-bound; retraining is heavy.
- **Scalability:** Adding mudras = re-collect/re-label/re-train the whole detector.
- **Explainability:** None (a class label + box; cannot say *why* it's wrong).
- **Suitability:** **Low as primary.** Reasonable only as a *hand detector* in cluttered/multi-hand scenes.

### Option 2 — MediaPipe Hands + Gesture Classification ★
- **Accuracy:** High on fine differences — features are explicit joint geometry; the discriminative signal is handed to the classifier directly.
- **Latency:** Excellent on **CPU** (MediaPipe is engineered for mobile real-time; ~10–30 ms CPU). No GPU needed.
- **Complexity:** Low–Medium (landmark extraction is a solved, pretrained component; classifier is tiny).
- **Hardware:** **CPU sufficient** (major cost/ops advantage).
- **Training difficulty:** Low — you label *class only* on auto-extracted landmark vectors; hundreds (not thousands) of samples because skin/light/bg are normalized away.
- **Deployment / Maintenance:** Light; retraining the small classifier is minutes, not GPU-hours.
- **Scalability:** Add a mudra = add labeled landmark samples + a class; the landmark stage is unchanged.
- **Explainability:** **Native** — per-joint deltas → human feedback. Uniquely satisfies your Explainable-AI requirement.
- **Suitability:** **Highest.** Cons: struggles under severe self-occlusion / extreme oblique angles; MediaPipe z is approximate (2.5D) so depth-only distinctions need care.

### Option 3 — YOLO + MediaPipe (detect hand, then landmark)
- **Accuracy:** High (same landmark classifier) — YOLO only crops the hand.
- **Latency:** Higher (two models) and needs GPU for YOLO.
- **Complexity / Maintenance:** Higher (two models, two trainings).
- **Suitability:** **Medium / over-engineered.** MediaPipe already *detects and tracks* the hand; bolting YOLO on front adds cost for little gain in a single-hand therapy setting. Justified only for multi-hand or very cluttered far-field scenes.

### Option 4 — Vision Transformer (ViT) classifier
- **Accuracy:** Potentially high with very large data; but data-hungry and still pixel-based (inherits context-overfit risk without huge datasets).
- **Latency / Hardware:** Heavy; GPU; poor edge/real-time fit.
- **Training / Maintenance:** Hard, expensive, slow to iterate.
- **Explainability:** Low (attention maps ≠ actionable finger feedback).
- **Suitability:** **Low.** Overkill; wrong tool for a geometry problem with modest data.

### Option 5 — Custom CNN (whole-image classifier)
- Same context-overfitting failure mode as Option 4/whole-frame classification; reinvents pretrained components; no explainability.
- **Suitability:** **Low.**

### Option 6 — Two-stage AI Pipeline
- This is the *generalized, productized* form of Option 2: **landmarks → classifier → explainer**, with the landmark stage swappable (MediaPipe today; a custom hand-keypoint model later).
- **Suitability:** **Highest** — it is the recommendation's architecture.

**Summary ranking for mudra recognition:** **Option 2/6 ≫ Option 3 > Option 1 > Option 4 ≈ Option 5.** Popularity (YOLO) loses to fit.

## 4. Mudra Analysis — is a bounding box enough? (the decisive argument)
Siddha/Natya mudras are differentiated almost entirely by **intra-hand geometry**:
- **Thumb position** (on top of fingers vs pointing up vs touching a fingertip) — *Mushti* vs *Shikhara* vs *Mayura*.
- **Which fingers are bent/straight** — *Pataka* vs *Tripataka* vs *Ardhapataka* (one or two fingers folded).
- **Finger spacing** (together vs spread) — *Pataka* vs *Kartarimukha*.
- **Finger touching** (a fingertip meeting the thumb) — *Mayura*, *Hamsasya*.
- **Palm orientation** (facing camera vs sideways).

**Would two mudras with identical bounding boxes but different finger positions confuse YOLO? — Yes, and here is precisely why:**
1. A bounding box encodes **location + extent + coarse in-box appearance**. *Mushti* (fist, thumb on top) and *Shikhara* (fist, thumb up) produce **the same box**; the only difference is a few-pixel thumb displacement.
2. The detector's classification head must infer that tiny difference from raw pixels while **large nuisance variation** (skin tone, lighting, background, hand size, angle) dominates the pixel signal. With limited data, the optimizer finds **shortcut features** (background, overall brightness, blob shape) that correlate spuriously with labels → **the v1 behaviour you observed**: a fist → `shikhar`, a spread hand → `shuktund`, *independent of the prescribed mudra*.
3. **Landmarks dissolve the problem:** thumb-tip coordinate relative to the index MCP, per-finger curl angles, inter-finger distances, and palm-normal orientation make *Mushti* vs *Shikhara* **linearly separable**, and the representation is **invariant to skin/light/background** by construction. The discriminative information is no longer buried under nuisances — it *is* the input.

**Therefore a bounding-box detector alone is insufficient for fine mudra discrimination.** This is the central, evidence-backed reason the recommendation is landmark-based, not detection-based.

## 5. Recommended Architecture
**Three-stage, landmark-first pipeline (served by FastAPI, behind the existing `InferenceClient`):**

1. **Hand landmark detection — MediaPipe Hands.** Per frame: detect the hand and emit **21 landmarks** (x, y, z≈2.5D) + handedness. Pretrained, real-time on CPU, mobile-ready.
2. **Mudra classification — lightweight geometric classifier.** Normalize landmarks (translate to wrist origin, scale by hand span, optionally rotate to a canonical frame) → engineered features (finger curl angles, tip-to-tip distances, thumb-index distance, finger-spread angles, palm-normal) → small **MLP / gradient-boosted classifier**, with **temporal smoothing** over recent frames for stability. Output: mudra class + calibrated confidence.
3. **Explainability comparator.** Compare the patient's normalized landmark vector to the **canonical template** of the *target* mudra → per-joint deltas → prioritized natural-language corrections (§7/AI Feedback).

**Why this architecture (CV justification):**
- **Invariance:** geometry, not pixels → robust to skin tone, lighting, background — the precise factors that broke v1.
- **Data efficiency:** nuisances removed → hundreds of samples/class suffice; labeling is *class-only* on auto-extracted landmarks.
- **Explainability:** coordinates enable actionable feedback; a detector cannot.
- **Cost / deployment:** **CPU real-time**; no GPU mandate.
- **Future fit:** MediaPipe runs **in-browser (WASM) and on mobile (TFLite)** → offline/edge inference later **without changing Laravel**.
- **Accuracy ceiling:** for distinct hand configurations, normalized-landmark classifiers routinely exceed pixel classifiers with a fraction of the data.

**Where YOLO still *could* help (optional, future):** a hand **detector** front-end for multi-hand or far-field cluttered scenes, or as a robustness fallback if MediaPipe's detector misses. Not needed for the core single-hand therapy flow.

## 6. System Architecture Diagram
```
┌─────────┐  640px JPEG   ┌──────────────── Laravel (UNCHANGED core) ────────────────┐
│ Browser │ ─────────────▶│ thin controller → VerifyPracticeAction (PURE)             │
│ camera  │◀── JSON ───── │   → InferenceClient (interface)                           │
│ +overlay│               │        │ bound to                                          │
└─────────┘               │        ▼                                                   │
                          │   ★ MediaPipeInferenceClient (NEW) ── HTTP ──┐             │
                          │   PracticeHoldTracker (cache)  ·  PracticeSessionService → DB │
                          │   PracticeVerified event → listeners                       │
                          └───────────────────────────────────────│────────────────────┘
                                                                   ▼
                ┌──────────────── FastAPI inference service (NEW, private) ────────────┐
                │ POST /predict (X-API-Key, X-Correlation-ID)                          │
                │   Stage 1: MediaPipe Hands → 21 landmarks (+handedness)              │
                │   Stage 2: normalize → features → classifier (+temporal smoothing)   │
                │   Stage 3: compare vs target template → feedback hints               │
                │   → { predictions[], landmarks?, feedback[], processing_time_ms }    │
                │ GET /health · GET /classes                                           │
                └──────────────────────────────────────────────────────────────────────┘
```

## 7. AI Workflow (per frame)
1. Browser captures + downscales the frame (≤640px) and POSTs to Laravel (existing route, unchanged).
2. Laravel `VerifyPracticeAction` (pure) calls `InferenceClient->detect()` → `MediaPipeInferenceClient` → FastAPI `/predict` (passes the *target* mudra so the service can compute feedback).
3. FastAPI: MediaPipe landmarks → normalize → classify → confidence; compare to target template → feedback hints; derive a bbox from landmark extents (so the existing overlay keeps working).
4. Response → mapped into the existing `MudraPrediction`/`InferenceResult`; `VerifyPracticeAction` applies the **business confidence threshold** (unchanged) and decides match.
5. `PracticeHoldTracker` accrues hold (unchanged); `PracticeSessionService` completes exactly-once + `PracticeVerified` (unchanged).
6. Feedback hints surfaced to the UI (additive field) for real-time coaching.

> The **verification policy stays in Laravel** (threshold, hold, completion). FastAPI only perceives and explains. Clean separation preserved.

## 8. Patient Learning Workflow (therapeutic UX)
This is a *teaching* system, so design the pre-practice learning, not just verification.

**Per-mudra "Learn" module (before practice):**
| Element | Purpose |
|---|---|
| High-quality reference photo(s) (multiple angles) | Ground truth of the correct shape |
| Animated GIF / short video tutorial | Shows the *transition* into the pose |
| Step-by-step finger placement (numbered) | Decomposes a complex shape |
| Rotatable 3D hand (future; from canonical landmarks) | Resolves orientation ambiguity |
| Benefits + therapeutic intent | Motivation/adherence |
| **Contraindications / cautions** | Safety (healthcare requirement) |
| Common mistakes (with ✗ images) | Pre-empts known errors |

**Guided practice flow:**
```
Learn mudra (study module)
   ↓ "I'm ready"
Camera on → live hand-landmark skeleton overlay
   ↓ real-time
Detection status (detected mudra + confidence)
   ↓
Explainable correction hints ("move thumb closer", "straighten index")  ← landmark-driven
   ↓ when correct
Hold-progress ring fills (PracticeHoldTracker) + "hold steady"
   ↓ optional voice guidance (TTS of the current hint)
Verified → completion animation → session auto-complete → dashboard "Done"
```
**Supporting UX:** progress indicator, AI confidence meter, finger-position hints, accessibility (large fonts, high contrast, captions for voice), graceful messaging when no hand is detected. (Reference photos/GIF/video/3D are *content assets* to be produced in Phase 1; the landmark skeleton overlay and live hints are the engineering deliverables uniquely enabled by this architecture.)

## 9. AI Feedback — Explainable, not binary
Binary "correct/wrong" is therapeutically useless. Because Stage 1 yields **coordinates** and we hold a **canonical template per mudra**, we compute targeted corrections:

| Signal computed from landmarks | Example feedback |
|---|---|
| Thumb-tip → index distance vs template | "Move your thumb closer to your index finger" |
| Per-finger curl angle vs template | "Straighten your index finger" / "Curl your ring finger more" |
| Inter-finger spread angles | "Bring your fingers together" / "Spread your fingers apart" |
| Palm-normal orientation | "Turn your palm toward the camera" / "Rotate your wrist slightly" |
| Temporal jitter of landmarks | "Hold steady" |
| Hand not fully in frame | "Move your hand into the frame" |

**Generation strategy:** rank deviations by magnitude (normalized), surface the **single most impactful** correction at a time (avoid overwhelming the patient), update as they adjust, and switch to "Hold steady — almost there" as the vector approaches the template. This *coaching loop* is the therapeutic core and is **only possible with a geometric representation** — the strongest product reason to choose landmarks over a detector.

## 10. Dataset Design
The label shifts from expensive **bounding boxes** to cheap **class labels on auto-extracted landmark vectors** — a major efficiency and quality win.

**Directory structure (dual-track):**
```
dataset/
├── raw/                          # source media (audit/reproducibility)
│   ├── videos/<mudra>/*.mp4      # short clips per subject per mudra
│   └── images/<mudra>/*.jpg
├── frames/<mudra>/*.jpg          # extracted frames (1–3 fps from videos)
├── landmarks/
│   ├── <mudra>.parquet           # MediaPipe-extracted 21×(x,y,z) + handedness + label + subject_id
│   └── manifest.json             # provenance, version, counts
├── templates/<mudra>.json        # canonical normalized landmark template (for the explainer)
├── splits/{train,valid,test}.csv # subject-disjoint splits
└── classes.yaml                  # single source of truth (mirrors Laravel ai_class_label)
```
*(If a future custom keypoint model is trained, add YOLO/COCO-keypoint labels under `keypoints/`; not needed while using MediaPipe.)*

**Collection recommendations & why:**
| Aspect | Recommendation | Why it matters |
|---|---|---|
| Capture | Short **videos** per subject/mudra → extract frames | One recording yields many varied frames cheaply |
| Frame extraction | 1–3 fps, dedup near-identical | Variety without redundancy |
| Samples/class | **300–600 landmark vectors** across ≥15–20 subjects | Generalize across hand shapes; balanced classes |
| Class balance | Within ~1.3× | Avoid bias toward majority |
| Skin tone | Fitzpatrick I–VI | Landmark detector robustness + fairness (also a medical-equity obligation) |
| Age groups | Young → elderly (target patients) | Joint mobility/skin differ; therapy users skew older |
| Lighting | Dim/bright/backlit/warm/cool | Landmark detector robustness |
| Background | Varied + cluttered | Detector robustness (classifier is already bg-invariant) |
| Camera angle | Frontal + ±15–30° | Patients aren't perfectly frontal; bounds the 2.5D weakness |
| Distance | Near + mid | Hold distance varies |
| Left & right hand | Both (store handedness) | Patients use either; templates per handedness or mirror-normalize |
| Occlusion | Include partial self-occlusion + recovery | The known landmark weak spot — characterize it |
| Negatives | "no hand"/random gestures | Reduce false positives |
| Label quality | Two-pass review; reject low-confidence landmark frames | Garbage landmarks → garbage classifier |
| Versioning | **DVC / Git-LFS**; semver the dataset + templates | Reproducible training; auditable for a medical system |

## 11. Training Strategy
- **Pipeline:** raw media → MediaPipe landmark extraction → normalize → feature engineering → train classifier → build canonical templates (per-mudra median of clean exemplars).
- **Split:** **subject-disjoint** train/valid/test (no person spans splits) — prevents identity leakage that inflates metrics.
- **Models to try (cheap to A/B):** MLP (2–3 layers) vs gradient-boosted trees vs SVM on the feature vector; pick by validation macro-F1 + calibration. Add a small **temporal model** (e.g., majority-vote / GRU over N frames) for live stability.
- **Hyperparameters:** modest — the input is low-dimensional. MLP: AdamW, lr 1e-3, dropout, early stopping on val macro-F1 (patience ~20). GBM: tune depth/estimators via CV. (Justify: low-dim geometric features need small models; large nets would overfit.)
- **Evaluation:** **confusion matrix first** (target the *confused pairs* — fist variants, one-finger-fold variants), per-class precision/recall, macro-F1, and **confidence calibration** (reliability curve) so the business threshold is meaningful.
- **False positives** (says correct when wrong): most harmful therapeutically → bias threshold/temporal hold to suppress; require the correct class held stably.
- **False negatives** (won't accept a correct pose): frustrating → analyze whether the *template* or the *threshold* is too strict; refine templates from real correct holds.
- **Acceptance criteria:** macro-F1 ≥ 0.95 on distinct mudras; **no confused pair with >5% cross-rate**; calibrated confidence; live demo passes manual QA across skin tones/lighting.
- **Continuous retraining:** with consent, log misclassified/low-confidence real landmark vectors (geometry only — *not* raw video → privacy-friendly), curate, retrain the small classifier (minutes), re-validate, version. The landmark stage rarely changes.

## 12. Deployment
**FastAPI + Docker; CPU-first (GPU optional).**
| Choice | Recommendation | Why |
|---|---|---|
| Service | FastAPI microservice behind `InferenceClient` | Mirrors the approved boundary; engine-agnostic |
| Container | **Docker (Linux)** | Reproducible Python/MediaPipe deps; healthcheck + restart |
| **GPU vs CPU** | **CPU is sufficient** (MediaPipe + tiny classifier run real-time on CPU) | Removes the GPU cost/ops burden YOLO implied — a decisive practical advantage |
| ONNX / TFLite | Export the classifier (+optionally landmark model) to **ONNX/TFLite** | Portability; mobile; future in-browser |
| TensorRT / OpenVINO | Only if a GPU/Intel-accelerated path is later needed | Not required for CPU real-time here |
| Topology | FastAPI co-located/private; never browser-facing | Latency + security |
| In-browser (future) | MediaPipe Tasks (WASM) in the browser; post *landmarks/result* to Laravel for server-authoritative verification | Offline/edge with **no Laravel change** |

## 13. Laravel Integration — minimum change
Identical to the InferenceClient-abstraction dividend, now with the explainability addition:
| Component | Change |
|---|---|
| `MediaPipeInferenceClient` (or generic `HttpInferenceClient`) | **NEW** — implements existing `InferenceClient`; maps FastAPI JSON → existing DTOs |
| `AiServiceProvider` + config | **driver switch** (`roboflow`\|`yolo`\|`mediapipe`); cutover/rollback by env |
| `DetectionResult` DTO | **+ optional `feedback: string[]` (and optional `landmarks`)** — *additive, backward-compatible*; the one justified change, required by the Explainable-AI goal |
| `VerifyPracticeAction` | **No** (still matches class vs target, applies threshold) |
| `PracticeHoldTracker`, `PracticeSessionService`, repositories, policies, events, controllers | **No** |
| `FakeInferenceClient` + existing tests | **No** (suite stays green) |
| `mudras.ai_class_label` | **Align** to `classes.yaml` (single source of truth) |
| Practice UI | **Additive** — render landmark skeleton overlay + feedback hints (the v1.0.2 UI already has the slots) |

The chain you specified is preserved exactly:
`Controller → VerifyPracticeAction → InferenceClient → AI provider → Prediction DTO → PracticeHoldTracker → PracticeSessionService`.

## 14. Security & Healthcare Privacy
- **Inference auth:** Laravel→FastAPI shared `X-API-Key` (env/secret store); FastAPI rejects without it; mTLS if cross-host.
- **Network:** FastAPI private/localhost; never browser-reachable.
- **Model protection:** weights/templates on read-only mounts; restricted replacement (supply-chain integrity).
- **Rate limiting:** Laravel already throttles `detect`; FastAPI per-key limit as defense in depth.
- **Privacy (medical):** prefer transmitting/logging **landmark vectors (geometry), not raw video**; do **not** persist frames; if frames are processed, process-in-memory and discard. Consent for any retained data; align to **DPDP (India)** and HIPAA-style minimization if applicable. Correlation IDs for audit without storing biometrics.
- **Bias/fairness:** validate accuracy across skin tones/ages (a safety + ethics requirement for a health tool).

## 15. Testing
| Layer | Test |
|---|---|
| Laravel unit | `MediaPipeInferenceClientTest` (`Http::fake`): JSON→DTO incl. feedback; error→`InferenceException`; API-key header |
| Laravel workflow | **Unchanged** via `FakeInferenceClient` — all hold/verify/exactly-once/dashboard tests stay green |
| FastAPI unit (pytest) | normalization math, feature extraction, classifier I/O, template comparison → feedback strings, schema/error mapping, health |
| Accuracy tests | held-out **subject-disjoint** set; confusion matrix; per-pair cross-rate gate; calibration |
| Integration | Laravel ↔ live FastAPI contract (separate stage; CI stays hermetic via fakes) |
| Performance/benchmark | CPU latency, p95 round-trip, throughput; target p95 < 100 ms |
| UAT / Manual QA | real patients across skin tones/ages/lighting/hands; feedback usefulness; occlusion behaviour |

## 16. Risks
| Risk | Sev | Mitigation |
|---|---|---|
| Self-occlusion / extreme angles degrade landmarks | Med | Frontal guidance in UI; temporal smoothing; characterize in dataset; YOLO/secondary view only if proven necessary |
| 2.5D depth ambiguity for orientation-only differences | Med | Use MediaPipe z + multi-feature; collect angle variety; accept small set needing care |
| Template/threshold too strict → false negatives | Med | Build templates from real correct holds; calibrate; per-mudra tolerances |
| Class-list drift (train vs `ai_class_label`) | Low | `classes.yaml` single source + CI sync check |
| MediaPipe dependency/licensing/version | Low | Pin; abstraction allows swapping the landmark engine (Option 6) |
| Dataset bias (skin/age) | High (ethics) | Mandated diversity targets + per-group acceptance gates |

## 17. Migration Plan (phased, gated, reversible)
0. **This review approved.** 1. Collect landmark dataset (videos→frames→landmarks) + build templates. 2. Train/validate classifier (acceptance gates §11). 3. Build FastAPI `/predict` (+`/health`,`/classes`) in Docker (CPU). 4. Laravel: `MediaPipeInferenceClient` + driver switch + additive `feedback` field + unit test (default driver stays current). 5. Align `ai_class_label`. 6. Integration + manual QA + benchmark. 7. Cutover via `INFERENCE_DRIVER=mediapipe`; rollback = flip env. POC on `master` untouched throughout.

## 18. Future Roadmap (no Laravel change)
- **Swap landmark/classifier internals** (custom keypoint model, GRU/Transformer temporal head) — behind `/predict`.
- **YOLOv12 / ONNX / TensorRT / OpenVINO** — only if a detector path is ever needed; behind the same seam.
- **MediaPipe in-browser (WASM) / mobile (TFLite)** → offline/edge; post results to Laravel to keep verification **server-authoritative**.
- **50+ mudras** — add labeled landmark samples + classes; landmark stage unchanged; near-linear scaling.
- **Multiple providers** — config-driven `InferenceClient` drivers already support this.

---

## ★ Final Recommendation
**Adopt a landmark-based two-stage pipeline: MediaPipe Hands (21 3D landmarks) → normalized geometric features → a lightweight classifier, plus a canonical-template comparator for explainable feedback. Serve it from FastAPI behind the existing `InferenceClient`. Do *not* use YOLO as the primary engine.**

**Why — strictly on CV/therapeutic merit, not popularity:**
1. **The problem is intra-hand geometry, not object localization.** Mudras differ by thumb/finger configuration that a bounding box cannot encode — proven by your v1 detector confusing fist and spread-hand variants regardless of intent.
2. **Invariance solves v1's actual failure.** Geometric features are skin-tone/lighting/background invariant; the very nuisances that broke v1 disappear by construction.
3. **It is the only option that delivers your Explainable-AI requirement.** Coordinates → actionable coaching ("move thumb closer", "straighten index"). A detector emits a label and cannot coach.
4. **Cheaper and faster to build and run.** CPU real-time, hundreds (not thousands) of samples, class-only labeling, minutes-long retraining — versus YOLO's GPU + heavy bbox dataset.
5. **Best future fit.** Mobile/edge/offline come for free via in-browser MediaPipe, with **zero Laravel change**.

**Reserve YOLO** strictly as an optional hand-detector front-end for future multi-hand/cluttered scenarios — not for mudra discrimination.

> Net: the popular choice (YOLO) is the wrong primary tool here; the *correct* choice is **MediaPipe Hands landmarks + a small classifier in a two-stage pipeline**. This is the official AI Architecture Blueprint for V2.
