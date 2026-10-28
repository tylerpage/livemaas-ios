import Foundation
import UIKit
import UniformTypeIdentifiers

// MARK: - DocumentPicker.Pick

/// Opens the system Files picker for PDFs, photos and videos.
/// Parameters:
///   - multiple: (optional) bool - allow picking several files (default true)
///   - id: (optional) string - echoed back on the event to correlate requests
/// Returns:
///   - (empty map — results arrive via the DocumentsPicked event)
enum DocumentPickerFunctions {

    class Pick: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let multiple = parameters["multiple"] as? Bool ?? true
            let id = parameters["id"] as? String

            DispatchQueue.main.async {
                DocumentPickerCoordinator.shared.open(multiple: multiple, id: id)
            }

            return [:]
        }
    }
}

// MARK: - Coordinator

final class DocumentPickerCoordinator: NSObject, UIDocumentPickerDelegate {
    static let shared = DocumentPickerCoordinator()

    private let eventClass = "Livemaas\\MobileDocumentPicker\\Events\\DocumentsPicked"
    private var pendingId: String?

    func open(multiple: Bool, id: String?) {
        pendingId = id

        // Same types the API accepts. asCopy gives us our own copy, so no
        // security-scoped access is needed to read the file afterwards.
        let picker = UIDocumentPickerViewController(forOpeningContentTypes: [.pdf, .image, .movie], asCopy: true)
        picker.allowsMultipleSelection = multiple
        picker.delegate = self

        if !DocumentPickerPresenter.present(picker) {
            send(files: [], cancelled: false, error: "No window to present the file picker from.")
        }
    }

    func documentPicker(_ controller: UIDocumentPickerViewController, didPickDocumentsAt urls: [URL]) {
        // Move each copy out of the picker's inbox into our own temp folder so
        // the path stays valid until PHP has finished uploading it.
        let folder = FileManager.default.temporaryDirectory.appendingPathComponent("picked-documents", isDirectory: true)
        try? FileManager.default.createDirectory(at: folder, withIntermediateDirectories: true)

        var files: [[String: Any]] = []

        for url in urls {
            let destination = folder.appendingPathComponent(UUID().uuidString + "-" + url.lastPathComponent)

            do {
                try FileManager.default.copyItem(at: url, to: destination)
            } catch {
                continue
            }

            let mimeType = UTType(filenameExtension: url.pathExtension)?.preferredMIMEType ?? "application/octet-stream"
            let size = (try? FileManager.default.attributesOfItem(atPath: destination.path))?[.size] as? Int ?? 0

            files.append([
                "path": destination.path,
                "name": url.lastPathComponent,
                "mimeType": mimeType,
                "size": size,
            ])
        }

        send(files: files, cancelled: false, error: files.isEmpty ? "The selected files could not be read." : nil)
    }

    func documentPickerWasCancelled(_ controller: UIDocumentPickerViewController) {
        send(files: [], cancelled: true, error: nil)
    }

    private func send(files: [[String: Any]], cancelled: Bool, error: String?) {
        var payload: [String: Any] = [
            "success": !files.isEmpty,
            "files": files,
            "count": files.count,
            "cancelled": cancelled,
        ]

        if let id = pendingId {
            payload["id"] = id
        }

        if let error = error {
            payload["error"] = error
        }

        pendingId = nil
        LaravelBridge.shared.send?(eventClass, payload)
    }
}

// MARK: - Presentation

/// Presents from the topmost view controller; UIKit ignores present() on a
/// controller that is already presenting something (e.g. a sheet).
enum DocumentPickerPresenter {
    @discardableResult
    static func present(_ viewController: UIViewController, animated: Bool = true) -> Bool {
        let scenes = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }
        let scene = scenes.first(where: { $0.activationState == .foregroundActive }) ?? scenes.first
        let window = scene?.windows.first(where: { $0.isKeyWindow }) ?? scene?.windows.first

        guard var top = window?.rootViewController else {
            return false
        }

        while let presented = top.presentedViewController {
            if presented.isBeingDismissed, let coordinator = presented.transitionCoordinator {
                coordinator.animate(alongsideTransition: nil) { _ in
                    DocumentPickerPresenter.present(viewController, animated: animated)
                }
                return true
            }

            top = presented
        }

        top.present(viewController, animated: animated)
        return true
    }
}
