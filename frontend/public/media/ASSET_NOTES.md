# Original media

## Student studio
- File: `student-studio.webp`
- Created with the built-in image_gen tool; converted to WebP for local delivery.
- Illustrative scene, not a real named student, customer endorsement or testimonial.
- Prompt:

Create one photorealistic-natural editorial photograph for a university student skills and career website, landscape 4:3 composition. A candid close-to-medium side/rear view of a Southeast Asian university student in their early twenties working thoughtfully at a laptop in a modern university project studio, another softly out-of-focus collaborator in the background. The main subject has natural human proportions and realistic skin texture, wears a simple muted blue overshirt, and is placed slightly right of centre; their hand rests naturally on the laptop trackpad. Screen shows subtle defocused software work, absolutely no readable UI text. Dark charcoal workspace, daylight from a high window creates believable directional illumination and tactile highlights, subtle cobalt and cool steel tones with small warm wood details. Rich editorial photography, restrained, authentic, aspirational, premium technology magazine, 35mm lens, shallow depth of field, good dynamic range, intentional composition with a little breathing room on the left and bottom. No logos, no watermarks, no text, no captions, no passport or booklet, no floating interfaces, no abstract geometric decoration, no neon lighting, no science fiction, no artificial graphic gradients. Not a posed corporate stock photo. This is an illustrative image, not a depiction of a real named student.

## Product walkthrough

- Current landing files: `product-walkthrough.mp4`, `product-walkthrough.webm`, `-small` variants and `product-walkthrough-poster.webp`.
- A **19-second recording of this application**, captured from the existing development services on 15 November 2026. Four clips show evidence submission, admin verification, a real Ollama job search and CV creation. Captions and the persistent **RECORDED DEMONSTRATION · EXAMPLE STUDENT** label distinguish the example from a real student's endorsement.
- The isolated example account used synthetic portfolio text and a labelled evidence PDF, plus actual provider listings and the actual local `qwen2.5:3b` model. It was removed after verification. Long waits were cut; the player caption explicitly says waits are shortened. One measured listing's score changed from 0 to 27.27 after verification; the result was not inserted into the video as a fabricated app score.
- Capture: `scripts/prepare_product_recording.php`, `scripts/record_product_scenario.mjs` via `browser_review.mjs`. Encode: `scripts/encode_product_recording.py` with FFmpeg. Capture frames remain temporary, outside the repository.
- Desktop MP4 557,722 bytes; WebM 562,602 bytes. Mobile MP4 244,293 bytes; WebM 258,104 bytes. Poster 47,580 bytes. Silent 24 fps output; desktop 1280 × 1000, mobile 768 × 600.
- `ProductVideo.vue` provides manual pause/play, visibility pause, `preload="none"`, responsive sources, reduced-motion and data-saving poster behaviour. Measured transfer checks and browser QA reports are generated locally and excluded from Git.
- Archived `skills-journey.*` files are the earlier original motion graphic rendered by `scripts/render_product_video.py`. They are retained for provenance but are no longer used by the landing page.

## Navi career companion

- File: `navi-bot.webp`, 480 × 480 with transparency, generated specifically for this project using the built-in image_gen tool and converted with FFmpeg.
- Original character, not an external brand mascot or a real person. No third-party animation package is required.
- The image has a blank face screen. `NaviAvatar.vue` overlays SVG eyes/mouth and uses CSS transforms for idle, thinking and read-aloud states. Pause and reduced-motion support are provided.
- Generation prompt:

Use case: stylized-concept. Asset type: original animated assistant character base image for a university skills and career web app. Create one premium friendly small 3D robot mascot, isolated on a truly transparent background, square composition. Front-facing symmetrical full body floating robot, large softly rounded rectangular head, pearl white ceramic shell, dark navy blank face screen with NO eyes and NO mouth (the application adds animated vector eyes), cobalt blue side ear discs, small aqua status light on chest, compact rounded body, two small arms with the right arm raised in a welcoming wave, small feet. Warm approachable proportions, beautiful tactile material, restrained subtle studio shading, clean smooth silhouette. Head face screen is centered horizontally, takes about 45 percent of image width at vertical position 30 percent. Entire character fits comfortably inside image with clear margin, no cropping. No text, no logos, no other objects, no shadow floor, no colored background, no graphic gradients or glow effects. Robot must look professionally modelled, friendly to university students and usable as an assistant avatar at small sizes.
