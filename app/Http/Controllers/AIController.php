<?php

namespace App\Http\Controllers;

use App\Models\OpenAIDocuments;
use App\Repositories\Contracts\OpenAIDocumentRepositoryInterface;
use Illuminate\Http\Request;
use App\Services\OpenAIStreamService;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{

    protected $openAIDocumentRepository;
    protected $queryString;
    protected $openAI;

    public function __construct(OpenAIStreamService $openAI, OpenAIDocumentRepositoryInterface $openAIDocumentRepository)
    {
        $this->openAI = $openAI;
        $this->openAIDocumentRepository = $openAIDocumentRepository;
    }

    public function getOpenAiDocuments(Request $request)
    {
        $queryString = (object) $request->all();
        $count = $this->openAIDocumentRepository->getOpenAiDocumentsCount($queryString);
        return response()->json($this->openAIDocumentRepository->getOpenAiDocuments($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function delete($id)
    {
        return OpenAIDocuments::findOrFail($id)->delete();
    }

    public function getOpenApiDocumentReponse($id)
    {
        return $this->openAIDocumentRepository->getOpenAiDocumentsResponse($id);
    }

    public function stream(Request $request)
    {
        $request->validate([
            'promptInput' => 'required|string|max:2000',
        ]);

        $prompt = $this->buildPrompt($request->all());
        $selectedModel = $request['selectedModel'] ?? 'gemini-2.0-flash';

        if (str_starts_with($selectedModel, 'gemini')) {
            return $this->streamGemini($selectedModel, $prompt, $request);
        } else {
            return $this->streamOpenAI($selectedModel, $prompt, $request);
        }
    }

    public function streamGemini($selectedModel, $prompt, $request)
    {
        try {
            if (env('GEMINI_API_KEY') == null || env('GEMINI_API_KEY') == '') {
                return response()->json([
                    'message' => 'Google Gemini API key is not set.',
                ], 404);
            }

            $buffer = '';

            // Create the streaming generator
            $stream = Gemini::generativeModel(model: $selectedModel)
                ->streamGenerateContent($prompt);

            // SSE headers
            $headers = [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                // For nginx proxy buffering disable:
                'X-Accel-Buffering' => 'no'
            ];

            // StreamedResponse will flush as chunks arrive
            return response()->stream(function () use ($stream, $prompt, $buffer, $request) {
                // Each $response is incremental partial generation object from the package
                foreach ($stream as $response) {
                    // $response->text() returns the partial text
                    $textChunk = $response->text();

                    // Build a JSON payload — your UI can parse and append
                    $payload = json_encode([
                        'text' => $textChunk
                    ], JSON_UNESCAPED_UNICODE);

                    $buffer .= $textChunk;
                    // SSE format: data: <payload>\n\n
                    echo "data: {$payload}\n\n";

                    // Flush buffers so the browser gets the chunk
                    @ob_flush();
                    @flush();

                    // Optional small sleep to avoid busy-looping
                    // usleep(1000);
                }

                $this->saveAIDocument($prompt, $buffer, $request);

                // End event to signal completion
                echo "data: " . json_encode(['text' => '##[[DONE]]##']) . "\n\n";
                @ob_flush();
                @flush();
            }, 200, $headers);
        } catch (\Throwable $th) {
            Log::error('Error in streamGemini: ' . $th->getMessage());
            return response()->json([
                'message' => 'Error while generating content' . $th->getMessage(),
            ], 404);
        }
    }

    public function streamOpenAI($selectedModel, $prompt, $request)
    {
        try {
            if (env('OPENAI_API_KEY') == null || env('OPENAI_API_KEY') == '') {
                return response()->json([
                    'message' => 'OpenAI API key is not set.',
                ], 404);
            }

            $stream = $this->openAI->streamChat($prompt, $selectedModel);
            $buffer = '';

            return response()->stream(function () use ($stream, $prompt, $buffer, $request) {
                foreach ($stream as $response) {
                    $content = $response->choices[0]->delta->content ?? '';

                    $buffer .= $content;

                    echo "data: " . json_encode(['text' => $content]) . "\n\n";
                    ob_flush();
                    flush();
                }

                $this->saveAIDocument($prompt, $buffer, $request);

                echo "data: " . json_encode(['text' => '##[[DONE]]##']) . "\n\n";
                ob_flush();
                flush();
                usleep(100); // 500
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error while generating content',
            ], 404);
        }
    }

    public function saveAIDocument($prompt, $response, $request)
    {
        OpenAIDocuments::create([
            'prompt' => $prompt,
            'response' => $response,
            'model' => $request['selectedModel'] ?? 'gpt-4',
            'language' => $request['language'] ?? null,
            'creativity' => $request['creativity'] ?? null,
            'maximumLength' => $request['maximumLength'] ?? null,
            'toneOfVoice' => $request['toneOfVoice'] ?? null
        ]);
    }

    public function buildPrompt($request)
    {
        $prompt = $request['promptInput'];

        if ($request['language']) {
            $prompt =  $prompt . ' Language is ' . $request['language'];
        }

        if ($request['creativity'] && (float)$request['creativity'] > 0) {
            $prompt =  $prompt . ' Creativity level is ' . $request['creativity'] . ' between 0 and 1';
        }

        if ($request['maximumLength'] && (int)$request['maximumLength'] > 0) {
            $prompt =  $prompt . ' Maximum ' . $request['maximumLength'] . ' words';
        }

        if ($request['toneOfVoice']) {
            $prompt =  $prompt . ' Tone of voice must be ' . $request['toneOfVoice'];
        }

        return $prompt;
    }
}
