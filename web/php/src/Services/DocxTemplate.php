<?php

namespace App\Services;

class DocxTemplate
{
    private array $placeholders = [];
    private string $content = '';
    private string $title = 'Document';
    private string $creator = 'PHP';
    private ?\DateTimeInterface $createdAt = null;

    public function __construct(string $content = '')
    {
        $this->content   = $content;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Set raw document content (with {{placeholders}})
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Set document title (stored in core properties)
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set document creator/author (core properties)
     */
    public function setCreator(string $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * Override created datetime for metadata
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Set placeholder value
     */
    public function set(string $key, string $value): self
    {
        $this->placeholders['{{' . $key . '}}'] = $value;
        return $this;
    }

    /**
     * Set multiple placeholders
     */
    public function setAll(array $data): self
    {
        foreach ($data as $k => $v) {
            $this->set($k, $v);
        }
        return $this;
    }

    /**
     * Replace placeholders
     */
    private function render(): string
    {
        return str_replace(
            array_keys($this->placeholders),
            array_values($this->placeholders),
            $this->content
        );
    }

    /**
     * Convert plain text into Word paragraphs
     */
    private function textToWordXML(string $text): string
    {
        $lines = preg_split("/\\r\\n|\\n|\\r/", $text);
        $xml   = '';

        foreach ($lines as $line) {
            $line = htmlspecialchars($line, ENT_XML1);

            $xml .= "
            <w:p>
                <w:r>
                    <w:t xml:space=\"preserve\">{$line}</w:t>
                </w:r>
            </w:p>";
        }

        return $xml;
    }

    /**
     * Generate DOCX
     */
    public function save(string $filename): void
    {
        $content = $this->render();
        $body    = $this->textToWordXML($content);

        $documentXML = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>
$body
<w:sectPr>
<w:pgSz w:w="12240" w:h="15840"/>
<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
</w:sectPr>
</w:body>
</w:document>
XML;

        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'docx');

        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create DOCX");
        }

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());

        // _rels/.rels
        $zip->addFromString('_rels/.rels', $this->rels());

        // word/document.xml
        $zip->addFromString('word/document.xml', $documentXML);

        // word/_rels/document.xml.rels
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRels());

        // docProps/core.xml (core document properties)
        $zip->addFromString('docProps/core.xml', $this->coreProps());

        // docProps/app.xml (application/extended properties)
        $zip->addFromString('docProps/app.xml', $this->appProps());

        $zip->close();

        if (!@copy($tmp, $filename)) {
            @unlink($tmp);
            throw new \RuntimeException("Failed to write DOCX file");
        }

        @unlink($tmp);
    }

    private function contentTypes(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/docProps/core.xml"
    ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml"
    ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
    }

    private function rels(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="word/document.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"
    Target="docProps/core.xml"/>
  <Relationship Id="rId3"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties"
    Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    private function documentRels(): string
    {
        // Empty for now; ready for images, headers, etc. later
        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>
XML;
    }

    /**
     * Core document properties (title, creator, created)
     */
    private function coreProps(): string
    {
        $created = $this->createdAt
            ? $this->createdAt->format('Y-m-d\TH:i:s\Z')
            : (new \DateTimeImmutable())->format('Y-m-d\TH:i:s\Z');

        $title   = htmlspecialchars($this->title, ENT_XML1);
        $creator = htmlspecialchars($this->creator, ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties
  xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:dcmitype="http://purl.org/dc/dcmitype/"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>{$title}</dc:title>
  <dc:creator>{$creator}</dc:creator>
  <cp:lastModifiedBy>{$creator}</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{$created}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{$created}</dcterms:modified>
</cp:coreProperties>
XML;
    }

    /**
     * Application/extended properties (very minimal)
     */
    private function appProps(): string
    {
        $app = htmlspecialchars('PHP DocxTemplate', ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties
  xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"
  xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>{$app}</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <LinksUpToDate>false</LinksUpToDate>
  <SharedDoc>false</SharedDoc>
  <HyperlinksChanged>false</HyperlinksChanged>
  <AppVersion>1.0</AppVersion>
</Properties>
XML;
    }
}
