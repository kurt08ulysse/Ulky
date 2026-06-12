import React from 'react';
import { TextInput, View, Text, TextInputProps } from 'react-native';
import { colors, spacing, typography } from '@/theme';

interface InputProps extends TextInputProps {
  label?: string;
  error?: string;
  helperText?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export function Input({
  label,
  error,
  helperText,
  leftIcon,
  rightIcon,
  style,
  editable = true,
  ...rest
}: InputProps) {
  const isError = Boolean(error);
  const isDisabled = editable === false;

  return (
    <View style={{ marginBottom: spacing.md }}>
      {label && (
        <Text
          style={{
            ...typography.caption,
            color: isError ? colors.error[600] : colors.text.secondary,
            marginBottom: spacing.sm,
            fontWeight: '600',
          }}
        >
          {label}
        </Text>
      )}

      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          height: 48,
          borderRadius: 8,
          borderWidth: 1,
          borderColor: isError ? colors.error[600] : colors.border.default,
          backgroundColor: isDisabled ? colors.neutral[100] : colors.background.default,
          paddingHorizontal: spacing.md,
          gap: spacing.sm,
        }}
      >
        {leftIcon && <View>{leftIcon}</View>}

        <TextInput
          style={[
            {
              flex: 1,
              ...typography.bodyLg,
              color: colors.text.primary,
              paddingVertical: spacing.sm,
            },
            style,
          ]}
          placeholderTextColor={colors.placeholder}
          editable={editable}
          {...rest}
        />

        {rightIcon && <View>{rightIcon}</View>}
      </View>

      {error && (
        <Text
          style={{
            ...typography.caption,
            color: colors.error[600],
            marginTop: spacing.xs,
          }}
        >
          ⚠️ {error}
        </Text>
      )}

      {helperText && !error && (
        <Text
          style={{
            ...typography.caption,
            color: colors.text.tertiary,
            marginTop: spacing.xs,
          }}
        >
          {helperText}
        </Text>
      )}
    </View>
  );
}
