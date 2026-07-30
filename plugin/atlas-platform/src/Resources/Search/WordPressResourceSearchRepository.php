<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Search;

final class WordPressResourceSearchRepository implements ResourceSearchRepository
{
    private string $resources; private string $versions; private string $citations; private string $sources;
    public function __construct(private object $database) { $this->resources=$database->prefix.'atlas_resources'; $this->versions=$database->prefix.'atlas_resource_versions'; $this->citations=$database->prefix.'atlas_citations'; $this->sources=$database->prefix.'atlas_sources'; }
    public function searchPublished(SearchCriteria $criteria, ?string $organizationId): SearchPage
    {
        $where=["r.archived_at IS NULL", "v.review_status = %s", "(r.scope IN ('platform','public') OR (r.scope = 'organization' AND r.organization_id = %s))"];
        $args=['published',$organizationId ?? ''];
        if ($criteria->query !== '') { $like='%'.$this->database->esc_like($criteria->query).'%'; $metadata=$this->database->prefix.'atlas_resource_metadata'; $where[]="(v.title LIKE %s OR v.summary LIKE %s OR r.slug LIKE %s OR EXISTS (SELECT 1 FROM `{$metadata}` m WHERE m.resource_id=r.id AND m.metadata_json LIKE %s))"; array_push($args,$like,$like,$like,$like); }
        if ($criteria->type !== null) { $where[]='r.resource_type = %s'; $args[]=$criteria->type; }
        $limit=$criteria->perPage+1; $offset=($criteria->page-1)*$criteria->perPage; $args[]=$limit; $args[]=$offset;
        $sourcePublisher="(SELECT s.publisher FROM `{$this->citations}` c INNER JOIN `{$this->sources}` s ON s.id = c.source_id WHERE c.resource_version_id = v.id ORDER BY c.display_order ASC, c.id ASC LIMIT 1)";
        $sourceTitle="(SELECT s.title FROM `{$this->citations}` c INNER JOIN `{$this->sources}` s ON s.id = c.source_id WHERE c.resource_version_id = v.id ORDER BY c.display_order ASC, c.id ASC LIMIT 1)";
        $sql="SELECT r.id, r.resource_type, r.scope, v.title, v.summary, v.review_status, v.effective_date, v.review_due_date, {$sourcePublisher} AS source_publisher, {$sourceTitle} AS source_title FROM `{$this->resources}` r INNER JOIN `{$this->versions}` v ON v.id = r.current_version_id WHERE ".implode(' AND ',$where)." ORDER BY CASE WHEN r.scope = 'organization' THEN 0 ELSE 1 END ASC, r.updated_at DESC, r.id ASC LIMIT %d OFFSET %d";
        $rows=$this->database->get_results($this->database->prepare($sql,...$args),ARRAY_A); if(!is_array($rows)){$rows=[];}
        $hasMore=count($rows)>$criteria->perPage; $rows=array_slice($rows,0,$criteria->perPage);
        $results=array_map(static fn(array $row):SearchResult=>new SearchResult((string)$row['id'],(string)$row['title'],(string)$row['summary'],(string)$row['resource_type'],(string)$row['scope'],(string)$row['review_status'],$row['effective_date']===null?null:(string)$row['effective_date'],$row['review_due_date']===null?null:(string)$row['review_due_date'],$row['source_publisher']===null?null:(string)$row['source_publisher'],$row['source_title']===null?null:(string)$row['source_title']),$rows);
        return new SearchPage($results,$criteria->page,$criteria->perPage,$hasMore);
    }
}
