<?php
namespace App\Services\Adapters;

class ImapAdapter
{
    protected array $cfg;
    protected $imapStream;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    protected function open(): void
    {
        if ($this->imapStream) {
            return;
        }
        $mailbox = sprintf('{%s:%d%s}%s', $this->cfg['host'], $this->cfg['port'], $this->cfg['flags'] ?? '', 'INBOX');
        $this->imapStream = @imap_open($mailbox, $this->cfg['username'], $this->cfg['password'], 0, 3);
        if (!$this->imapStream) {
            throw new \RuntimeException('IMAP connect failed: ' . imap_last_error());
        }
    }

    public function fetch(string $folder = 'INBOX', int $limit = 25): array
    {
        $this->open();
        imap_reopen($this->imapStream, sprintf('{%s:%d%s}%s', $this->cfg['host'], $this->cfg['port'], $this->cfg['flags'] ?? '', $folder));

        $mails = imap_search($this->imapStream, 'ALL', SE_UID, 'UTF-8');
        if (!$mails) {
            return [];
        }

        rsort($mails); // newest first
        $uids = array_slice($mails, 0, $limit);
        $result = [];

        foreach ($uids as $uid) {
            $header = imap_headerinfo($this->imapStream, imap_msgno($this->imapStream, $uid));
            $structure = imap_fetchstructure($this->imapStream, imap_msgno($this->imapStream, $uid));
            $body = imap_fetchbody($this->imapStream, imap_msgno($this->imapStream, $uid), 1.1) ?: imap_fetchbody($this->imapStream, imap_msgno($this->imapStream, $uid), 1);
            $result[] = [
                'uid' => $uid,
                'subject' => isset($header->subject) ? imap_utf8($header->subject) : '',
                'from' => $header->fromaddress ?? '',
                'date' => $header->date ?? '',
                'body' => $body,
            ];
        }

        return $result;
    }

    public function __destruct()
    {
        if ($this->imapStream) {
            @imap_close($this->imapStream);
        }
    }
}
