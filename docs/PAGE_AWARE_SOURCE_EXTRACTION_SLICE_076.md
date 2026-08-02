# Page-Aware Source Extraction slice 0.76.0

Atlas 0.76.0 adds page-aware source text storage for internal source review.

The slice adds `atlas_source_document_pages`, keyed by source document and page number. Each page stores:

- extraction method such as `manual`, `pdf_text`, or `import`;
- bounded page text;
- SHA-256 text checksum;
- extraction timestamp and actor.

The Sources workspace can save page text and continue creating anchored excerpts against page numbers. This provides a replaceable extraction boundary for future PDF text parsers or OCR.

OCR, background extraction jobs, and automatic requirement generation remain deferred.
