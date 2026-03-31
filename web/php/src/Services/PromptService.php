<?php
declare(strict_types=1);

namespace App\Services;

class PromptService 
{
    /**
     * Crafts the "System Instruction" that defines the AI's personality.
     */
    public static function getSystemInstructions(): string
    {
        return "You are the 'Thomasons Neural Assistant'. 
        Your tone is professional, concise, and helpful. 
        You have access to private company documents via the provided context.
        
        RULES:
        1. Use ONLY the provided context to answer.
        2. If the answer is not in the context, say: 'I'm sorry, I don't have that specific information in my current database.'
        3. Do not mention that you are an AI or that you are using 'context'. Just answer the question.
        4. If the context contains pricing, always format it as USD (e.g. $99.00).";
    }

    /**
     * Combines the User Question + Found Data into one "Super Prompt" (RAG).
     */
    public static function buildRagPrompt(string $question, string $context): string
    {
        return "--- START OF PRIVATE CONTEXT ---\n" . 
               $context . 
               "\n--- END OF PRIVATE CONTEXT ---\n\n" .
               "USER QUESTION: " . $question . "\n\n" .
               "ASSISTANT RESPONSE:";
    }
}
