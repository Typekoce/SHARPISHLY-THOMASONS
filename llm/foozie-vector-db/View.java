public class View {
// Location: llm/foozie-vector-dm

    public void info(String msg) {
        System.out.println(msg);
    }

    public void bestMatch(String id, double score, String metadata) {
        System.out.println("Best match: " + id);
        System.out.println("Score: " + score);
        System.out.println("Metadata: " + metadata);
    }

    public void match(String id, double score, String metadata) {
        System.out.println(
                id +
                " | score=" + score +
                " | " + metadata
        );
    }

    public void listCount(int count) {
        System.out.println("Total vectors: " + count);
    }
}
