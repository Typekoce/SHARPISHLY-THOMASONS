import java.util.List;
// Location: llm/foozie-vector-dm

public class Controller {

    private final Model model;
    private final View view;

    public Controller(String storageFile) {
        this.model = new Model(storageFile);
        this.view = new View();
    }

    public void runDemo() {

        view.info("Adding vectors...");

        model.add("doc1", new double[]{0.1, 0.2, 0.3}, "first document");
        model.add("doc2", new double[]{0.2, 0.1, 0.4}, "second document");
        model.add("doc3", new double[]{0.9, 0.8, 0.7}, "third document");

        view.listCount(model.all().size());

        double[] query = {0.15, 0.18, 0.28};

        view.info("\nBest match:");

        Model.VectorItem best = model.findBest(query);

        if (best != null) {
            view.bestMatch(
                    best.id,
                    model.similarity(query, best.vector),
                    best.metadata
            );
        }

        view.info("\nTop 3 matches:");

        List<Model.VectorItem> top = model.findTopK(query, 3);

        for (Model.VectorItem item : top) {
            view.match(
                    item.id,
                    model.similarity(query, item.vector),
                    item.metadata
            );
        }

        view.info("\nDeleting doc2...");
        boolean deleted = model.delete("doc2");

        view.info("Deleted: " + deleted);
        view.listCount(model.all().size());
    }
}
