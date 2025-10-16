<?php

namespace App\Services;

use App\Models\Documents;
use OpenAI;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AISummaryService
{
    public function generateSummary(string $model, string $documentId)
    {
        try {
            if (str_starts_with($model, 'gemini') && env('GEMINI_API_KEY') == '') {
                return response()->json(['Message' => 'Google Gemini API key is not set.',], 503);
            }

            if (str_starts_with($model, 'gpt') && env('OPENAI_API_KEY') == '') {
                return response()->json(['Message' => 'OpenAI API key is not set.'], 503);
            }

            $document = Documents::find($documentId);
            if (!$document) {
                return response()->json(['Message' => "Document not found."], 404);
            }

            $ext = strtolower(pathinfo($document->url, PATHINFO_EXTENSION));
            if (!in_array($ext, ['txt', 'pdf', 'docx', 'doc'])) {
                return response()->json(['Message' => "Unsupported document format for summarization: {$ext}"], 409);
            }

            $content = $this->getDocumentContent($document);
            if ($content === null || trim($content) === '') {
                return response()->json(['Message' => "Unable to extract text from document: {$document->name}"], 409);
            }

            $tokenLimit = config("ai.models.{$model}", 16000);
            $approxTokens = $this->estimateTokens($content);

            if ($approxTokens <= $tokenLimit) {
                $prompt = "Summarize the following text in clear and concise language:\n\n" . $content;
                $summary = $this->summarize($model, $prompt);
                return response()->json(['summary' => $summary], 200);
            }

            $chunks = $this->chunkText($content, (int)($tokenLimit / 2));
            $chunkSummaries = [];

            foreach ($chunks as $chunk) {
                $chunkPrompt = "Summarize this text clearly and concisely:\n\n" . $chunk;
                $chunkSummaries[] = $this->summarize($model, $chunkPrompt);
            }

            $finalPrompt = "Combine and refine the following summaries into a single concise overall summary:\n\n" . implode("\n\n", $chunkSummaries);
            $summary = $this->summarize($model, $finalPrompt);
            return response()->json(['summary' => $summary], 200);
        } catch (\Throwable $th) {
            return response()->json(['Message' => "Error generating summary: " . $th->getMessage()], 500);
        }
    }

    private function getDocumentContent(Documents $document): ?string
    {
        $location = $document->location ?? 'local';

        if (!$document->url) return null;

        try {
            $contents = null;
            if (Storage::disk($location)->exists($document->url)) {
                $contents = Storage::disk($location)->get($document->url);
            }

            if ($contents === null || $contents === false) {
                Log::warning('Could not read document contents', ['path' => $document->url, 'location' => $location]);
                return null;
            }

            $ext = strtolower(pathinfo($document->url, PATHINFO_EXTENSION));

            if ($ext === 'txt') return $contents;

            if ($ext === 'pdf') return $this->extractTextFromPdf($contents);

            if ($ext === 'docx') return $this->extractTextFromDocx($contents);

            if ($ext === 'doc') return $this->extractTextFromDoc($contents);

            return $contents;
        } catch (\Throwable $e) {
            Log::error('getDocumentContent error: ' . $e->getMessage());
            return null;
        }
    }

    private function extractTextFromPdf(string $contents): ?string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseContent($contents);
            return $pdf->getText();
        } catch (\Throwable $e) {
            Log::warning('PDF parser failed: ' . $e->getMessage());
            return null;
        }
    }

    private function extractTextFromDocx(string $contents): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tmp, $contents);
        $zip = new \ZipArchive();
        if ($zip->open($tmp) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            @unlink($tmp);
            if ($xml) {
                $xml = str_replace(['<w:p>', '</w:p>', '<w:tab/>'], ["\n", "\n", "\t"], $xml);
                $text = strip_tags($xml);
                return preg_replace('/\s+/', ' ', $text);
            }
        }
        @unlink($tmp);
        return null;
    }

    private function extractTextFromDoc(string $contents): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($tmp, $contents);

        @exec('antiword ' . escapeshellarg($tmp) . ' 2>&1', $lines, $ret);
        if ($ret === 0 && !empty($lines)) {
            @unlink($tmp);
            return implode("\n", $lines);
        }

        @exec('catdoc ' . escapeshellarg($tmp) . ' 2>&1', $lines2, $ret2);
        if ($ret2 === 0 && !empty($lines2)) {
            @unlink($tmp);
            return implode("\n", $lines2);
        }

        @unlink($tmp);
        return null;
    }

    private function summarize(string $model, string $text): string
    {
        if (str_starts_with($model, 'gpt')) {
            $openai = OpenAI::factory()
                ->withApiKey(env('OPENAI_API_KEY'))
                ->withHttpClient(
                    new \GuzzleHttp\Client([
                        'verify' => false,
                    ])
                )->make();

            $response = $openai->chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that summarizes documents into clear and concise language.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
            ]);

            return $response->choices[0]->message->content ?? '';
        }

        if (str_starts_with($model, 'gemini')) {
            $stream = Gemini::generativeModel(model: $model)
                ->streamGenerateContent($text);

            $summary = '';
            foreach ($stream as $event) {
                if ($event->text()) {
                    $summary .= $event->text();
                }
            }
            return $summary;
        }

        return '';
    }

    private function estimateTokens(string $text): int
    {
        return (int) (strlen($text) / 4);
    }

    private function chunkText(string $text, int $maxTokens): array
    {
        $maxChars = max(1, $maxTokens) * 4;
        return str_split($text, $maxChars);
    }
}
