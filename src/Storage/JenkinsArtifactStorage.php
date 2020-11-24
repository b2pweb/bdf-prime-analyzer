<?php

namespace Bdf\Prime\Analyzer\Storage;

use BadMethodCallException;
use Bdf\Prime\Analyzer\Report;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstant;
use Bdf\Prime\Analyzer\Storage\Instant\ReportInstantFactory;

/**
 * Storage for retrieve last successful build's report
 * This storage is read only
 */
final class JenkinsArtifactStorage implements ReportStorageInterface
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $project;

    /**
     * @var string
     */
    private $branch;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $token;


    /**
     * JenkinsArtifactStorage constructor.
     * 
     * @param string $baseUrl
     * @param string $project
     * @param string $branch
     * @param string $file
     * @param string $username
     * @param string $token
     */
    public function __construct(string $baseUrl, string $project, string $branch, string $file, string $username, string $token)
    {
        $this->baseUrl = rtrim($baseUrl, '/'); // Remove tailing /
        $this->project = $project;
        $this->branch = $branch;
        $this->file = $file;
        $this->username = $username;
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function push(ReportInstant $instant, array $reports): void
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReportInstant $instant): array
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function instants(ReportInstantFactory $instantFactory): array
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function last(ReportInstantFactory $instantFactory): ?array
    {
        $context = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic '.base64_encode($this->username.':'.$this->token)
            ]
        ]);

        $content = @file_get_contents($this->baseUrl.'/job/'.$this->project.'/job/'.$this->branch.'/lastSuccessfulBuild/artifact/'.$this->file, false, $context);
        $data = @unserialize($content, ['allowed_classes' => [Report::class]]);

        return is_array($data) ? $data : null;
    }
}
