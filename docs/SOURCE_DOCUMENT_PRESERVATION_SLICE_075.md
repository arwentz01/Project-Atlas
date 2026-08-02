# Source Document Preservation slice 0.75.0

Atlas 0.75.0 adds internal PDF preservation for source documents.

The Sources workspace can now accept an optional PDF file when registering a source document. The upload handler:

- requires the source-upload capability and admin nonce;
- accepts only PDF files under 25 MB;
- writes the file under an Atlas-controlled uploads subdirectory;
- records original filename, MIME type, size, preservation timestamp, and SHA-256 checksum;
- keeps the private filesystem path out of source evidence displays and patient-facing packet flows.

This slice preserves source artifacts for staff review. It does not add OCR, background extraction, public download links, or patient-facing file output.
