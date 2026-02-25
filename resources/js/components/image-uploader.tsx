import { useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Loader2, UploadCloud, X } from 'lucide-react';

export function ImageUploader({
    name,
    defaultValue,
    onUploadSuccess,
    className,
}: {
    name: string;
    defaultValue?: string;
    onUploadSuccess?: (url: string) => void;
    className?: string;
}) {
    const [preview, setPreview] = useState<string | null>(defaultValue ?? null);
    const [isUploading, setIsUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploading(true);
        const formData = new FormData();
        formData.append('image', file);
        formData.append('directory', 'menu-items');

        try {
            // Because the frontend is served by Laravel, we can hit the API directly
            const response = await fetch('/api/v1/upload', {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error('Upload failed');
            }

            const data = await response.json();
            const url = data.data.url;
            setPreview(url);
            if (onUploadSuccess) {
                onUploadSuccess(url);
            }
        } catch (error) {
            console.error('File upload error:', error);
            alert('Failed to upload image. Please try again.');
        } finally {
            setIsUploading(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = ''; // Reset input
            }
        }
    };

    return (
        <div className={`flex flex-col gap-2 ${className ?? ''}`}>
            {/* Hidden text input to actually submit the URL string in the main form state */}
            <input type="hidden" name={name} value={preview ?? ''} />
            
            {preview ? (
                <div className="relative h-24 w-24 overflow-hidden rounded-md border">
                    <img src={preview} alt="Preview" className="h-full w-full object-cover" />
                    <button
                        type="button"
                        onClick={() => {
                            setPreview(null);
                            if (onUploadSuccess) onUploadSuccess('');
                        }}
                        className="absolute right-1 top-1 rounded-full bg-black/50 p-1 text-white hover:bg-black/70"
                    >
                        <X className="h-3 w-3" />
                    </button>
                </div>
            ) : (
                <div 
                    onClick={() => fileInputRef.current?.click()}
                    className="flex h-24 w-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed hover:bg-muted"
                >
                    {isUploading ? (
                        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                    ) : (
                        <>
                            <UploadCloud className="h-5 w-5 text-muted-foreground" />
                            <span className="text-[10px] text-muted-foreground">Upload</span>
                        </>
                    )}
                </div>
            )}
            
            <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={handleFileChange}
                disabled={isUploading}
            />
        </div>
    );
}
